<?php

namespace Osit\LogSentinel\Http\Controllers;

use Composer\InstalledVersions;
use Illuminate\Routing\Controller;

class SettingsController extends Controller
{
    public function index()
    {
        return view('log-sentinel::settings.index', [
            'package' => [
                'name' => config('log-sentinel.package_name', 'Laravel Log Sentinel'),
                'composer_name' => 'osit/laravel-log-sentinel',
                'version' => $this->packageVersion(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug_enabled' => config('app.debug'),
                'timezone' => config('app.timezone'),
            ],

            'routePrefix' => config('log-sentinel.route_prefix'),
            'middleware' => config('log-sentinel.middleware', []),

            'sourceTypes' => config('log-sentinel.source_types', []),
            'activeParsers' => config('log-sentinel.active_parsers', []),

            'pathSecurity' => config('log-sentinel.path_security', []),
            'retention' => config('log-sentinel.retention', []),
            'redaction' => config('log-sentinel.redaction', []),

            'commands' => [
                'Install package' => 'php artisan log-sentinel:install',
                'Scan logs' => 'php artisan log-sentinel:scan',
                'Detect alerts' => 'php artisan log-sentinel:detect-alerts --limit=5000',
                'Prune logs dry-run' => 'php artisan log-sentinel:prune --dry-run',
                'Prune logs' => 'php artisan log-sentinel:prune',
            ],

            'schedulerExamples' => [
                "Schedule::command('log-sentinel:scan')->everyFiveMinutes();",
                "Schedule::command('log-sentinel:prune')->dailyAt('02:00');",
            ],
        ]);
    }

    private function packageVersion(): string
    {
        try {
            return InstalledVersions::getPrettyVersion('osit/laravel-log-sentinel') ?: 'dev';
        } catch (\Throwable) {
            return 'dev';
        }
    }
}
