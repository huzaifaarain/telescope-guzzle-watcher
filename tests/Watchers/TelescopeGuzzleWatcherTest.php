<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests\Watchers;

use GuzzleHttp\Client;
use Laravel\Telescope\EntryType;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests\TestCase;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers\TelescopeGuzzleWatcher;

class TelescopeGuzzleWatcherTest extends TestCase
{
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        if (! class_exists(\GuzzleHttp\Client::class)) {
            $this->markTestSkipped('The "guzzlehttp/guzzle" composer package is required for this test.');
        }

        $app->get('config')->set('telescope.watchers', [
            TelescopeGuzzleWatcher::class => true,
        ]);
    }

    /**
     * @test
     */
    public function it_should_intercept_and_log_request()
    {
        $client = app(Client::class);
        try {
            $client->get('https://www.google.com');
        } catch (\Throwable $th) {
            report($th);
        }

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertNotNull($entry);
        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('GET', $entry->content['method']);
        $this->assertSame('https://www.google.com', $entry->content['uri']);
    }
}
