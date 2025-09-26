<?php

declare(strict_types=1);

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\ClientRequestWatcher;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\GuzzleClientFactory;
use Override;

class TelescopeGuzzleWatcher extends ClientRequestWatcher
{
    public ?Request $request = null;

    public ?Response $response = null;

    public function __construct(

    ) {
        // if ($transferStats instanceof TransferStats) {
        //     $this->request = new Request($transferStats->getRequest());
        //     if ($transferStats->hasResponse()) {
        //         $this->response = new Response($transferStats->getResponse());
        //     }
        // }
    }

    public static function createFrom(TransferStats $transferStats): void
    {
        $instance = new self;
        $instance->request = new Request($transferStats->getRequest());
        if ($transferStats->hasResponse()) {
            $instance->response = new Response($transferStats->getResponse());
        }

        $instance->record();
    }

    private function record(): void
    {
        if (! Telescope::isRecording()) {
            return;
        }

        $incomingEntry = IncomingEntry::make([
            'method' => $this->request->method(),
            'uri' => strtok($this->request->url(), '?'),
            'headers' => $this->headers($this->request->headers()),
            'payload' => [
                'query_string' => $this->queryString(),
                'payload' => $this->payload($this->input($this->request)),
            ],
            'response_status' => $this->response instanceof Response ? $this->response->status() : null,
            'response_headers' => $this->response instanceof Response ? $this->headers($this->response->headers()) : null,
            'response' => $this->response instanceof Response ? $this->response($this->response) : null,
            'duration' => $this->response instanceof Response ? $this->duration($this->response) : null,
        ]);

        if (config('telescope-guzzle-watcher.enable_uri_tags') === true) {
            $incomingEntry->tags($this->extractTagsFromUri());
        }

        Telescope::recordClientRequest($incomingEntry);
    }

    /**
     * Extract the query string from the given request url
     */
    protected function queryString(): array
    {
        parse_str(parse_url($this->request->url(), PHP_URL_QUERY) ?? '', $queryString);

        return $queryString;
    }

    private function extractTagsFromUri()
    {
        $uri = $this->request->url();
        $parsedURI = parse_url((string) $uri);
        $tags = [$parsedURI['host']];
        if (array_key_exists('path', $parsedURI)) {
            $pathArr = array_filter(explode('/', $parsedURI['path']));
            $tags = array_merge($tags, $pathArr);
        }

        $exceptTags = config('telescope-guzzle-watcher.exclude_words_from_uri_tags');
        if (count($exceptTags) > 0) {
            $tags = Arr::where($tags, fn ($tag): bool => ! in_array($tag, $exceptTags));
        }

        return $tags;
    }

    #[Override]
    public function register($app): void
    {
        $app->bind(Client::class, fn ($app, array $config) => app(GuzzleClientFactory::class)($config));
    }

    /**
     * Extract the input from the given request.
     *
     * @return array
     */
    #[Override]
    protected function input(Request $request)
    {
        if (! $request->isMultipart()) {
            return $request->data();
        }

        return collect(preg_split("/--.*\r\n/", (string) $request->data()))
            ->filter()
            ->values()
            ->mapWithKeys(function ($content): array {
                $contentArray = collect(preg_split("/\r\n/", $content))
                    ->filter()
                    ->values();

                $key = $contentArray->firstWhere(fn ($content): bool => str_contains($content, 'name='));

                if ($hasContentType = $contentArray->search(fn ($contentItem): bool => str_contains($contentItem, 'Content-Type'))) {
                    $contentArray = $contentArray->filter(fn ($contentItem, $index): bool => $index <= $hasContentType);
                }

                $contentName = str($key)->match("/name\=[\',\"](\w*)[\',\"]/")->toString();

                return [$contentName => $contentArray];
            })->toArray();
    }
}
