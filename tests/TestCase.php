<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeServiceProvider;
use MuhammadHuzaifa\TelescopeGuzzleWatcher\TelescopeGuzzleWatcherServiceProvider;
use Orchestra\Testbench\TestCase as TestBenchTestCase;

class TestCase extends TestBenchTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('terminateTelescope', [$this, 'terminateTelescope']);

        Telescope::flushEntries();
    }

    protected function tearDown(): void
    {
        Telescope::flushEntries();

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            TelescopeServiceProvider::class,
            TelescopeGuzzleWatcherServiceProvider::class,
        ];
    }

    protected function resolveApplicationCore($app)
    {
        parent::resolveApplicationCore($app);

        $app->detectEnvironment(function () {
            return 'self-testing';
        });
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('logging.default', 'errorlog');

        $config->set('database.default', 'testbench');

        $config->set('telescope.storage.database.connection', 'testbench');

        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give('testbench');
    }

    protected function loadTelescopeEntries()
    {
        $this->terminateTelescope();

        return EntryModel::all();
    }

    public function terminateTelescope()
    {
        Telescope::store(app(EntriesRepository::class));
    }

    protected function beforeRefreshingDatabase()
    {
        if (version_compare($this->app->version(), '11.0.0', '>=')) {
            $config = $this->app->get('config');
            $config->set('database.migrations.update_date_on_publish', false);
        }

        $hasPublishedMigration = collect(glob(base_path('/database/migrations/*.php')))
            ->filter(fn ($file) => str_contains($file, 'telescope_entries'))
            ->count();
        if (! $hasPublishedMigration) {
            $this->artisan('vendor:publish', ['--tag' => 'telescope-migrations']);
        }
    }
}
