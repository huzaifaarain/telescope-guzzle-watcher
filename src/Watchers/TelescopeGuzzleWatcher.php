<?php

declare(strict_types=1);

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers;

use GuzzleHttp\Client;
use Laravel\Telescope\Watchers\Watcher;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\TelescopeGuzzleClientFactory;

class TelescopeGuzzleWatcher extends Watcher
{
    public function register($app): void
    {
        $app->bind(Client::class, fn ($app, array $config) => app(TelescopeGuzzleClientFactory::class)($config));
    }
}
