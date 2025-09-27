<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests\Watchers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\Request;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use LogicException;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\GuzzleClientFactory;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests\TestCase;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers\TelescopeGuzzleWatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(TelescopeGuzzleWatcher::class)]
#[UsesClass(GuzzleClientFactory::class)]
final class TelescopeGuzzleWatcherTest extends TestCase
{
    private bool $originalShouldRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalShouldRecord = Telescope::$shouldRecord;
        Telescope::flushEntries();
    }

    protected function tearDown(): void
    {
        Telescope::$shouldRecord = $this->originalShouldRecord;
        Telescope::flushEntries();

        parent::tearDown();
    }

    #[Test]
    public function it_records_transfer_stats_with_response_details(): void
    {
        Telescope::$shouldRecord = true;

        config()->set('telescope-guzzle-watcher.enable_uri_tags', true);
        config()->set('telescope-guzzle-watcher.exclude_words_from_uri_tags', ['api']);

        $stats = $this->makeTransferStats(
            method: 'POST',
            uri: 'https://example.com/api/users?filter=active',
            requestHeaders: [
                'Content-Type' => ['application/json'],
                'X-Request-Id' => ['abc-123'],
            ],
            requestBody: json_encode(['name' => 'Jane Doe'], JSON_THROW_ON_ERROR),
            response: new PsrResponse(
                201,
                ['Content-Type' => 'application/json'],
                json_encode(['ok' => true], JSON_THROW_ON_ERROR)
            ),
            transferTime: 0.245
        );

        TelescopeGuzzleWatcher::createFrom($stats);

        $this->assertCount(1, Telescope::$entriesQueue);

        $entry = Telescope::$entriesQueue[0];

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame('https://example.com/api/users', $entry->content['uri']);
        $this->assertSame(['filter' => 'active'], $entry->content['payload']['query_string']);
        $this->assertSame(['name' => 'Jane Doe'], $entry->content['payload']['payload']);
        $this->assertSame(201, $entry->content['response_status']);
        $this->assertSame('application/json', $entry->content['response_headers']['content-type']);
        $this->assertSame(['ok' => true], $entry->content['response']);
        $this->assertSame(245.0, $entry->content['duration']);
        $this->assertSame(['example.com', 'users'], $entry->tags);
    }

    #[Test]
    public function it_handles_missing_response_and_disabled_tags(): void
    {
        Telescope::$shouldRecord = true;

        config()->set('telescope-guzzle-watcher.enable_uri_tags', false);

        $stats = $this->makeTransferStats(
            method: 'GET',
            uri: 'https://status.example.org',
        );

        TelescopeGuzzleWatcher::createFrom($stats);

        $this->assertCount(1, Telescope::$entriesQueue);

        $entry = Telescope::$entriesQueue[0];

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('GET', $entry->content['method']);
        $this->assertSame('https://status.example.org', $entry->content['uri']);
        $this->assertSame([], $entry->content['payload']['query_string']);
        $this->assertSame([], $entry->content['payload']['payload']);
        $this->assertNull($entry->content['response_status']);
        $this->assertNull($entry->content['response_headers']);
        $this->assertNull($entry->content['response']);
        $this->assertNull($entry->content['duration']);
        $this->assertSame([], $entry->tags);
    }

    #[Test]
    public function it_skips_recording_when_telescope_not_recording(): void
    {
        Telescope::$shouldRecord = false;

        $stats = $this->makeTransferStats(
            method: 'DELETE',
            uri: 'https://example.com/resources/1'
        );

        TelescopeGuzzleWatcher::createFrom($stats);

        $this->assertSame([], Telescope::$entriesQueue);
    }

    #[Test]
    public function it_registers_guzzle_client_binding(): void
    {
        $watcher = new TelescopeGuzzleWatcher;
        $watcher->register($this->app);

        $client = $this->app->make(Client::class, ['config' => ['base_uri' => 'https://example.com']]);

        $this->assertInstanceOf(Client::class, $client);
    }

    #[Test]
    public function it_requires_config_parameter_to_be_array(): void
    {
        $watcher = new TelescopeGuzzleWatcher;
        $watcher->register($this->app);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("\$parameters['config'] must be associative array");

        $this->app->make(Client::class, ['config' => 'not-an-array']);
    }

    #[Test]
    public function it_returns_request_data_for_non_multipart_requests(): void
    {
        $watcher = $this->makeInputAwareWatcher();

        $psrRequest = new PsrRequest(
            'POST',
            'https://example.com',
            ['Content-Type' => 'application/json'],
            json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR)
        );

        $request = (new Request($psrRequest))->withData(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $watcher->exposeInput($request));
    }

    #[Test]
    public function it_parses_multipart_form_data_payload(): void
    {
        $watcher = $this->makeInputAwareWatcher();

        $boundary = '----Boundary123';
        $body = implode("\r\n", [
            "--{$boundary}",
            'Content-Disposition: form-data; name="token"',
            '',
            'secret-token',
            "--{$boundary}",
            'Content-Disposition: form-data; name="file"; filename="debug.txt"',
            'Content-Type: text/plain',
            '',
            'example-content',
            "--{$boundary}--",
            '',
        ]);

        $psrRequest = new PsrRequest(
            'POST',
            'https://example.com/upload',
            ['Content-Type' => "multipart/form-data; boundary={$boundary}"],
            $body
        );

        $request = new Request($psrRequest);

        $parsed = $watcher->exposeInput($request);

        $this->assertSame([
            'token' => [
                'Content-Disposition: form-data; name="token"',
                'secret-token',
            ],
            'file' => [
                'Content-Disposition: form-data; name="file"; filename="debug.txt"',
                'Content-Type: text/plain',
            ],
        ], $parsed);
    }

    #[Test]
    public function it_returns_empty_tags_for_relative_urls(): void
    {
        Telescope::$shouldRecord = true;

        config()->set('telescope-guzzle-watcher.enable_uri_tags', true);

        $stats = $this->makeTransferStats(
            method: 'GET',
            uri: '/relative/path'
        );

        TelescopeGuzzleWatcher::createFrom($stats);

        $this->assertCount(1, Telescope::$entriesQueue);
        $this->assertSame([], Telescope::$entriesQueue[0]->tags);
    }

    #[Test]
    public function it_returns_empty_payload_for_empty_multipart_body(): void
    {
        $watcher = $this->makeInputAwareWatcher();

        $boundary = '----Boundary123';
        $psrRequest = new PsrRequest(
            'POST',
            'https://example.com/upload',
            ['Content-Type' => "multipart/form-data; boundary={$boundary}"],
            ''
        );

        $request = new Request($psrRequest);

        $this->assertTrue($request->isMultipart());
        $this->assertSame([], $watcher->exposeInput($request));
    }

    #[Test]
    public function it_skips_sections_without_name_attribute(): void
    {
        $watcher = $this->makeInputAwareWatcher();

        $boundary = '----Boundary123';
        $body = implode("\r\n", [
            "--{$boundary}",
            'Content-Disposition: form-data;',
            '',
            'value-without-name',
            "--{$boundary}--",
            '',
        ]);

        $psrRequest = new PsrRequest(
            'POST',
            'https://example.com/upload',
            ['Content-Type' => "multipart/form-data; boundary={$boundary}"],
            $body
        );

        $request = new Request($psrRequest);

        $this->assertTrue($request->isMultipart());
        $this->assertSame([], $watcher->exposeInput($request));
    }

    #[Test]
    public function it_skips_sections_with_empty_name_attribute(): void
    {
        $watcher = $this->makeInputAwareWatcher();

        $boundary = '----Boundary123';
        $body = implode("\r\n", [
            "--{$boundary}",
            'Content-Disposition: form-data; name=""',
            '',
            'value-with-empty-name',
            "--{$boundary}--",
            '',
        ]);

        $psrRequest = new PsrRequest(
            'POST',
            'https://example.com/upload',
            ['Content-Type' => "multipart/form-data; boundary={$boundary}"],
            $body
        );

        $request = new Request($psrRequest);

        $this->assertTrue($request->isMultipart());
        $this->assertSame([], $watcher->exposeInput($request));
    }

    #[Test]
    public function it_returns_empty_payload_when_sections_are_missing(): void
    {
        $watcher = $this->makeInputAwareWatcher();

        $boundary = '----Boundary123';
        $body = "--{$boundary}\r\n--{$boundary}--\r\n";

        $psrRequest = new PsrRequest(
            'POST',
            'https://example.com/upload',
            ['Content-Type' => "multipart/form-data; boundary={$boundary}"],
            $body
        );

        $request = new Request($psrRequest);

        $this->assertTrue($request->isMultipart());
        $this->assertSame([], $watcher->exposeInput($request));
    }

    private function makeInputAwareWatcher(): object
    {
        return new class extends TelescopeGuzzleWatcher
        {
            public function exposeInput(Request $request): array
            {
                return $this->input($request);
            }
        };
    }

    private function makeTransferStats(
        string $method,
        string $uri,
        array $requestHeaders = [],
        ?string $requestBody = null,
        ?PsrResponse $response = null,
        float $transferTime = 0.0
    ): TransferStats {
        $request = new PsrRequest(
            $method,
            $uri,
            $requestHeaders,
            Utils::streamFor($requestBody ?? '')
        );

        return new TransferStats(
            $request,
            $response,
            $transferTime,
            []
        );
    }
}
