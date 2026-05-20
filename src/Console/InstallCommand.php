<?php

namespace Osit\LogSentinel\Console;

use Illuminate\Console\Command;
use Osit\LogSentinel\Models\LogSource;

class InstallCommand extends Command
{
    protected $signature = 'log-sentinel:install
        {--force : Overwrite published files}
        {--with-default-source : Create the default Laravel log source}';

    protected $description = 'Install Laravel Log Sentinel resources and optionally create the default Laravel log source.';

    public function handle(): int
    {
        $this->newLine();
        $this->info('Installing Laravel Log Sentinel...');
        $this->newLine();

        $force = (bool) $this->option('force');

        $this->callSilent('vendor:publish', [
            '--tag' => 'log-sentinel-config',
            '--force' => $force,
        ]);

        $this->line('Published config file.');

        $this->callSilent('vendor:publish', [
            '--tag' => 'log-sentinel-views',
            '--force' => $force,
        ]);

        $this->line('Published views.');

        $this->callSilent('vendor:publish', [
            '--tag' => 'log-sentinel-migrations',
            '--force' => $force,
        ]);

        $this->line('Published migrations.');

        $this->newLine();

        if ($this->option('with-default-source')) {
            $this->createDefaultLaravelSource();
        } elseif ($this->confirm('Would you like to create the default Laravel log source now?', true)) {
            $this->createDefaultLaravelSource();
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Run migrations:');
        $this->line('   php artisan migrate');

        $this->newLine();
        $this->line('2. Scan logs:');
        $this->line('   php artisan log-sentinel:scan');

        $this->newLine();
        $this->line('3. Detect alerts for existing logs:');
        $this->line('   php artisan log-sentinel:detect-alerts --limit=5000');

        $this->newLine();
        $this->line('4. Add these to your scheduler, usually in routes/console.php:');
        $this->line("   Schedule::command('log-sentinel:scan')->everyFiveMinutes();");
        $this->line("   Schedule::command('log-sentinel:prune')->dailyAt('02:00');");

        $this->newLine();
        $this->info('Laravel Log Sentinel installation complete.');
        $this->newLine();

        return self::SUCCESS;
    }

    private function createDefaultLaravelSource(): void
    {
        $path = storage_path('logs/laravel.log');

        LogSource::query()->firstOrCreate(
            [
                'name' => 'Laravel Main Log',
            ],
            [
                'type' => 'laravel',
                'parser' => 'laravel',
                'path' => $path,
                'enabled' => true,
                'scan_interval_minutes' => 5,
                'retention_days' => config('log-sentinel.retention.default_log_days', 30),
            ]
        );

        $this->line('Default Laravel log source created or already exists.');
    }
}
