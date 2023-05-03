<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TelescopeGuzzleWatcherServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('telescope-guzzle-watcher')
            ->hasConfigFile();
    }
}
