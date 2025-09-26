<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Illuminate\Foundation\Application;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\Watcher;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\TelescopeGuzzleRecorder;

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
        return static function ($app, array $parameters): Client {
            $config = $parameters['config'] ?? [];
            if (Telescope::isRecording()) {
                $onStatsClosure = $config['on_stats'] ?? null;
                $config['on_stats'] = function (TransferStats $stats) use ($onStatsClosure) {
                    TelescopeGuzzleRecorder::recordGuzzleRequest($stats);
                    if ($onStatsClosure instanceof Closure) {
                        $onStatsClosure($stats);
                    }
                };
            }

            return new Client(
                config: $config,
            );
        };
    }
}
