<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests\Unit;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\TransferStats;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\GuzzleClientFactory;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests\TestCase;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers\TelescopeGuzzleWatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(GuzzleClientFactory::class)]
#[UsesClass(TelescopeGuzzleWatcher::class)]
final class GuzzleClientFactoryTest extends TestCase
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
    public function it_returns_client_without_wrapping_when_not_recording(): void
    {
        Telescope::$shouldRecord = false;

        $factory = new GuzzleClientFactory();

        $onStats = static function (): void {
        };

        $client = $factory([
            'base_uri' => 'https://example.com',
            'on_stats' => $onStats,
        ]);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame($onStats, $client->getConfig('on_stats'));
    }

    #[Test]
    public function it_wraps_existing_on_stats_and_records_transfer(): void
    {
        Telescope::$shouldRecord = true;
        Telescope::flushEntries();

        $factory = new GuzzleClientFactory();

        $called = false;
        $client = $factory([
            'on_stats' => static function (TransferStats $stats) use (&$called): void {
                $called = $stats instanceof TransferStats;
            },
        ]);

        $onStats = $client->getConfig('on_stats');
        $this->assertInstanceOf(Closure::class, $onStats);

        $stats = $this->makeTransferStats();

        $onStats($stats);

        $this->assertTrue($called);
        $this->assertCount(1, Telescope::$entriesQueue);
        $this->assertSame(EntryType::CLIENT_REQUEST, Telescope::$entriesQueue[0]->type);
    }

    #[Test]
    public function it_sets_on_stats_when_missing_in_config(): void
    {
        Telescope::$shouldRecord = true;
        Telescope::flushEntries();

        $factory = new GuzzleClientFactory();

        $client = $factory([
            'base_uri' => 'https://example.com',
        ]);

        $onStats = $client->getConfig('on_stats');
        $this->assertInstanceOf(Closure::class, $onStats);

        $onStats($this->makeTransferStats());

        $this->assertCount(1, Telescope::$entriesQueue);
    }

    private function makeTransferStats(): TransferStats
    {
        $request = new PsrRequest(
            'PUT',
            'https://example.com/posts/1?lang=en',
            ['Content-Type' => 'application/json'],
            Utils::streamFor(json_encode(['body' => 'example'], JSON_THROW_ON_ERROR))
        );

        $response = new PsrResponse(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR)
        );

        return new TransferStats(
            $request,
            $response,
            0.1,
            []
        );
    }
}
