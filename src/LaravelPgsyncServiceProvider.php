<?php

namespace Alancolant\LaravelPgsync;

use Alancolant\LaravelPgsync\Commands\Listen;
use Alancolant\LaravelPgsync\Commands\Prepare;
use Illuminate\Support\ServiceProvider;

class LaravelPgsyncServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('laravel-pgsync.php'),
            ], 'config');

            // Registering package commands.
            $this->commands([
                Listen::class,
                Prepare::class
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'laravel-pgsync');
    }
}
