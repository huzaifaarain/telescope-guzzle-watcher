<?php

declare(strict_types=1);

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\ClientRequestWatcher;
use LogicException;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\GuzzleClientFactory;
use Override;

class TelescopeGuzzleWatcher extends ClientRequestWatcher
{
    public ?Request $request = null;

    public ?Response $response = null;

    public static function createFrom(TransferStats $transferStats): void
    {
        $instance = new self;
        $instance->request = new Request($transferStats->getRequest());
        if ($transferStats->hasResponse()) {
            $response = $transferStats->getResponse();
            if ($response instanceof ResponseInterface) {
                $instance->response = new Response($response);
                $instance->response->transferStats = $transferStats;
            }
        }

        $instance->record();
    }

    private function record(): void
    {
        if (!Telescope::isRecording() || ! $this->request instanceof Request) {
            return;
        }

        $incomingEntry = IncomingEntry::make([
            'method' => $this->request->method(),
            'uri' => strtok($this->request->url(), '?'),
            'headers' => $this->headers($this->request->headers()),
            'payload' => [
                'query_string' => $this->queryString($this->request),
                'payload' => $this->payload($this->input($this->request)),
            ],
            'response_status' => $this->response instanceof Response ? $this->response->status() : null,
            'response_headers' => $this->response instanceof Response ? $this->headers($this->response->headers()) : null,
            'response' => $this->response instanceof Response ? $this->response($this->response) : null,
            'duration' => $this->response instanceof Response ? $this->duration($this->response) : null,
        ]);

        if (config('telescope-guzzle-watcher.enable_uri_tags') === true) {
            $incomingEntry->tags($this->extractTagsFromUri($this->request));
        }

        Telescope::recordClientRequest($incomingEntry);
    }

    /**
     * Extract the query string from the given request url.
     *
     * @return array<int|string, mixed>
     */
    protected function queryString(Request $request): array
    {
        $queryString = [];
        $query = parse_url($request->url(), PHP_URL_QUERY) ?? '';
        parse_str(is_string($query) ? $query : '', $queryString);

        return $queryString;
    }

    /**
     * @return array<int, string>
     */
    private function extractTagsFromUri(Request $request): array
    {
        $parsedUri = parse_url($request->url()) ?: [];

        if (! isset($parsedUri['host'])) {
            return [];
        }

        $tags = [$parsedUri['host']];

        if (isset($parsedUri['path'])) {
            foreach (explode('/', $parsedUri['path']) as $segment) {
                $segment = trim($segment);

                if ($segment === '') {
                    continue;
                }

                $tags[] = $segment;
            }
        }

        $exceptTags = (array) config('telescope-guzzle-watcher.exclude_words_from_uri_tags', []);
        if ($exceptTags !== []) {
            $filteredExclusions = array_filter($exceptTags, static fn ($value): bool => is_string($value));
            $lowerExcluded = array_map(static fn (string $value): string => strtolower($value), $filteredExclusions);
            $tags = array_values(array_filter(
                $tags,
                static fn (string $tag): bool => ! in_array(strtolower($tag), $lowerExcluded, true)
            ));
        }

        return $tags;
    }

    #[Override]
    public function register($app): void
    {
        $app->bind(function ($app, array $parameters): Client {
            if (Arr::exists($parameters, 'config') && !is_array($parameters['config'])) {
                throw new LogicException("\$parameters['config'] must be associative array");
            }

            /** @var GuzzleClientFactory $guzzleClientFactory */
            $guzzleClientFactory = app(GuzzleClientFactory::class);

            /** @var array<string, mixed> $config */
            $config = $parameters['config'] ?? [];

            return $guzzleClientFactory($config);
        });
    }

    /**
     * Extract the input from the given request.
     *
     * @return array<string, mixed>
     */
    #[Override]
    protected function input(Request $request): array
    {
        if (!$request->isMultipart()) {
            /** @var array<string, mixed> $data */
            $data = $request->data();

            return $data;
        }

        $rawBody = $request->body();

        if ($rawBody === '') {
            return [];
        }

        $sections = preg_split('/--.*\r\n/', $rawBody, limit: -1, flags: PREG_SPLIT_NO_EMPTY) ?: [];

        if ($sections === []) {
            return [];
        }

        $payload = [];

        foreach ($sections as $section) {
            $trimmedSection = trim($section);

            $lines = preg_split("/\r\n/", $trimmedSection, limit: -1, flags: PREG_SPLIT_NO_EMPTY) ?: [];

            $lines = array_map(static fn ($line): string => trim($line), $lines);

            $keyLine = null;
            foreach ($lines as $line) {
                if (str_contains($line, 'name=')) {
                    $keyLine = $line;
                    break;
                }
            }

            if ($keyLine === null) {
                continue;
            }

            $contentName = Str::match("/name=['\"]([^'\"]+)['\"]/i", $keyLine);

            if ($contentName === '') {
                continue;
            }

            $contentLines = $lines;
            $contentTypeIndex = array_find_key($contentLines, fn($line): bool => str_contains((string) $line, 'Content-Type'));

            if ($contentTypeIndex !== null) {
                $contentLines = array_slice($contentLines, 0, $contentTypeIndex + 1);
            }

            $payload[$contentName] = $contentLines;
        }

        return $payload;
    }
}
