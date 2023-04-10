<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\FetchesStackTrace;
use Laravel\Telescope\Watchers\Watcher;

class TelescopeGuzzleWatcher extends Watcher
{
    use FetchesStackTrace;

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
                $config['on_stats'] = function (TransferStats $stats) {
                    $stats->getResponse()->getBody()->rewind();
                    $stats->getRequest()->getBody()->rewind();
                    $requestBody = json_decode($stats->getRequest()->getBody()->getContents() ?? [], true);
                    $queryString = null;
                    parse_str($stats->getRequest()->getUri()->getQuery(), $queryString);
                    $payload = array_merge(["queryString" => $queryString ?? []], ["body" => $requestBody ?? []]);
                    Telescope::recordClientRequest(
                        entry: IncomingEntry::make([
                            'method' => $stats->getRequest()->getMethod(),
                            'uri' => strtok($stats->getRequest()->getUri(), "?"),
                            'headers' => Arr::except($stats->getRequest()->getHeaders(), config('telescope-guzzle-watcher.except_request_headers', [])),
                            'payload' => $payload,
                            'response_status' => $stats->getResponse()->getStatusCode(),
                            'response_headers' => Arr::except($stats->getResponse()->getHeaders(), config('telescope-guzzle-watcher.except_request_headers', [])),
                            'response' => json_decode($stats->getResponse()->getBody()->getContents(), true),
                        ]),
                    );
                };
            }

            return new Client(
                config: $config,
            );
        };
    }
}
