<?php

namespace Osit\LogSentinel;

use Illuminate\Support\ServiceProvider;
use Osit\LogSentinel\Console\ScanLogsCommand;
use Osit\LogSentinel\Console\DetectAlertsCommand;
use Osit\LogSentinel\Console\PruneLogsCommand;
use Osit\LogSentinel\Console\InstallCommand;

class LogSentinelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/log-sentinel.php',
            'log-sentinel'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'log-sentinel'
        );

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/log-sentinel.php' => config_path('log-sentinel.php'),
        ], 'log-sentinel-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/log-sentinel'),
        ], 'log-sentinel-views');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'log-sentinel-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ScanLogsCommand::class,
                DetectAlertsCommand::class,
                PruneLogsCommand::class,
            ]);
        }
    }
}
