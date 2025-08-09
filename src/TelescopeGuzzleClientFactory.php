<?php

declare(strict_types=1);

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Laravel\Telescope\Telescope;

class TelescopeGuzzleClientFactory
{
    public function __invoke(array $config): Client
    {
        if (Telescope::isRecording()) {
            $onStatsClosure = $config['on_stats'] ?? null;
            $config['on_stats'] = function (TransferStats $transferStats) use ($onStatsClosure): void {
                TelescopeGuzzleRecorder::recordGuzzleRequest($transferStats);
                if ($onStatsClosure instanceof Closure) {
                    $onStatsClosure($transferStats);
                }
            };
        }

        return new Client(
            config: $config,
        );
    }
}
