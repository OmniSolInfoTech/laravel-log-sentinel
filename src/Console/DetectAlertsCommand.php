<?php

namespace Osit\LogSentinel\Console;

use Illuminate\Console\Command;
use Osit\LogSentinel\Models\LogEntry;
use Osit\LogSentinel\Services\AlertDetector;

class DetectAlertsCommand extends Command
{
    protected $signature = 'log-sentinel:detect-alerts {--limit=1000 : Number of log entries to process}';

    protected $description = 'Run alert detection against existing log entries.';

    public function handle(AlertDetector $alertDetector): int
    {
        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            $limit = 1000;
        }

        $count = 0;

        LogEntry::query()
            ->latest('occurred_at')
            ->limit($limit)
            ->get()
            ->each(function (LogEntry $entry) use ($alertDetector, &$count) {
                $alertDetector->detect($entry);
                $count++;
            });

        $this->info("Alert detection complete. Processed {$count} log entries.");

        return self::SUCCESS;
    }
}
