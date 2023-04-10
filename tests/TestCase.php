<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\TelescopeGuzzleWatcherServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Factory::guessFactoryNamesUsing(
        //     fn (string $modelName) => 'MuhammadHuzaifa\\TelescopeGuzzleWatcher\\Database\\Factories\\'.class_basename($modelName).'Factory'
        // );
    }

    protected function getPackageProviders($app)
    {
        // return [
        //     TelescopeGuzzleWatcherServiceProvider::class,
        // ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_telescope-guzzle-watcher_table.php.stub';
        $migration->up();
        */
    }
}
