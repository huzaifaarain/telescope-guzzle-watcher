<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests\Unit;

use MuhammadHuzaifa\TelescopeGuzzleWatcher\TelescopeGuzzleWatcherServiceProvider;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(TelescopeGuzzleWatcherServiceProvider::class)]
final class TelescopeGuzzleWatcherServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_package_configuration(): void
    {
        $this->app->register(TelescopeGuzzleWatcherServiceProvider::class);

        $config = config('telescope-guzzle-watcher');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enable_uri_tags', $config);
        $this->assertTrue($config['enable_uri_tags']);
    }
}
