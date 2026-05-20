<?php

namespace Osit\LogSentinel\Console;

use Illuminate\Console\Command;
use Osit\LogSentinel\Models\LogSource;
use Osit\LogSentinel\Services\LogScanner;

class ScanLogsCommand extends Command
{
    protected $signature = 'log-sentinel:scan {--source_id= : Scan one specific log source ID}';

    protected $description = 'Scan configured log sources and import new log entries.';

    public function handle(LogScanner $scanner): int
    {
        $sourceId = $this->option('source_id');

        if ($sourceId) {
            $source = LogSource::find($sourceId);

            if (! $source) {
                $this->error("Log source {$sourceId} was not found.");
                return self::FAILURE;
            }

            $count = $scanner->scan($source);

            $this->info("Scanned source {$source->name}. Imported {$count} new entries.");

            return self::SUCCESS;
        }

        $count = $scanner->scan();

        $this->info("Log Sentinel scan complete. Imported {$count} new entries.");

        return self::SUCCESS;
    }
}
