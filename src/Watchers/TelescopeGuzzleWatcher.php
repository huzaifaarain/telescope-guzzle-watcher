<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\Watcher;

class TelescopeGuzzleWatcher extends Watcher
{

    public function register($app)
    {
        $app->bind(
            abstract: Client::class,
            concrete: $this->buildClient(
                app: $app,
            ),
        );
    }

    private function buildClient(Application $app): Closure
    {
        return static function ($app, array $config): Client {
            if (Telescope::isRecording()) {
                $onStatsClosure = $config['on_stats'] ?? null;
                $config['on_stats'] = function (TransferStats $stats) use ($onStatsClosure) {
                    Telescope::recordClientRequest(entry: static::makeEntry(stats: $stats));
                    if ($onStatsClosure && is_callable($onStatsClosure)) {
                        $onStatsClosure($stats);
                    }
                };
            }

            return new Client(
                config: $config,
            );
        };
    }

    private static function makeEntry(TransferStats $stats): IncomingEntry
    {
        $stats->getResponse()?->getBody()->rewind();
        $stats->getRequest()?->getBody()->rewind();
        $requestBody = json_decode($stats->getRequest()?->getBody()->getContents() ?? "", true);
        $queryString = null;
        parse_str($stats->getRequest()?->getUri()->getQuery(), $queryString);
        $payload = array_merge(["queryString" => $queryString ?? []], ["body" => $requestBody ?? []]);

        $entry = IncomingEntry::make([
            'method' => $stats->getRequest()->getMethod(),
            'uri' => strtok($stats->getRequest()->getUri(), "?"),
            'headers' => Arr::except($stats->getRequest()->getHeaders(), config('telescope-guzzle-watcher.except_request_headers', [])),
            'payload' => $payload,
            'response_status' => $stats->getResponse()->getStatusCode(),
            'response_headers' => Arr::except($stats->getResponse()->getHeaders(), config('telescope-guzzle-watcher.except_request_headers', [])),
            'response' => json_decode($stats->getResponse()->getBody()->getContents(), true)
        ]);

        if (Auth::check()) {
            $entry->user(Auth::user());
        }

        if (config('telescope-guzzle-watcher.enable_uri_tags') === true) {
            $entry->tags(static::extractTagsFromUri($stats->getRequest()->getUri()));
        }

        return $entry;
    }

    private static function extractTagsFromUri(string $uri)
    {
        $parsedURI = parse_url($uri);
        $tags = [$parsedURI['host']];
        if (array_key_exists("path", $parsedURI)) {
            $pathArr = array_filter(explode("/", $parsedURI['path']));
            $tags = array_merge($tags, $pathArr);
        }

        $exceptTags = config('telescope-guzzle-watcher.exclude_words_from_uri_tags');
        if (count($exceptTags) > 0) {
            $tags = Arr::where($tags, function ($tag) use ($exceptTags) {
                return !in_array($tag, $exceptTags);
            });
        }

        return $tags;
    }
}
