<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as TestBenchTestCase;

use function Orchestra\Testbench\default_skeleton_path;

#[WithMigration('telescope')]
class TestCase extends TestBenchTestCase
{
    use RefreshDatabase, WithLaravelMigrations, WithWorkbench;

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
        if (
            collect(scandir(default_skeleton_path('database/migrations')))
                ->filter(
                    fn ($migration) => str_contains($migration, 'telescope')
                )
                ->count() == 0
        ) {
            $this->artisan('vendor:publish', ['--tag' => 'telescope-migrations']);
        }

    }
}
