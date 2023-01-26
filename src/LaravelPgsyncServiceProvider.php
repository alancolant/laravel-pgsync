<?php

namespace Alancolant\LaravelPgsync;

use Alancolant\LaravelPgsync\Commands\FakeData;
use Alancolant\LaravelPgsync\Commands\Listen;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelPgsyncServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-pgsync')
            ->hasConfigFile()
            ->hasMigration('create_pgsync_notify_trigger_function')
            ->hasCommands([Listen::class, FakeData::class]);
    }
}
