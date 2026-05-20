<?php

namespace Osit\LogSentinel\Console;

use Illuminate\Console\Command;
use Osit\LogSentinel\Models\LogEntry;
use Osit\LogSentinel\Models\LogSource;
use Osit\LogSentinel\Models\SecurityAlert;

class PruneLogsCommand extends Command
{
    protected $signature = 'log-sentinel:prune
        {--dry-run : Show what would be deleted without deleting anything}
        {--source_id= : Prune one specific source only}
        {--days= : Override retention days for this run}
        {--include-open-alert-entries : Also prune entries linked to open or acknowledged alerts}';

    protected $description = 'Prune old Log Sentinel entries and old resolved alerts.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sourceId = $this->option('source_id');
        $daysOverride = $this->option('days');
        $includeOpenAlertEntries = (bool) $this->option('include-open-alert-entries');

        $this->info('Starting Log Sentinel prune...');

        if ($dryRun) {
            $this->warn('Dry-run mode enabled. No records will be deleted.');
        }

        $totalEntriesPruned = 0;

        $sourcesQuery = LogSource::query();

        if ($sourceId) {
            $sourcesQuery->where('id', $sourceId);
        }

        $sources = $sourcesQuery->get();

        foreach ($sources as $source) {
            $retentionDays = $daysOverride
                ? (int) $daysOverride
                : (int) ($source->retention_days ?: config('log-sentinel.retention.default_log_days', 30));

            $count = $this->pruneEntriesForSource(
                source: $source,
                retentionDays: $retentionDays,
                dryRun: $dryRun,
                includeOpenAlertEntries: $includeOpenAlertEntries
            );

            $totalEntriesPruned += $count;
        }

        if (! $sourceId) {
            $orphanCount = $this->pruneOrphanEntries(
                retentionDays: $daysOverride
                    ? (int) $daysOverride
                    : (int) config('log-sentinel.retention.default_log_days', 30),
                dryRun: $dryRun,
                includeOpenAlertEntries: $includeOpenAlertEntries
            );

            $totalEntriesPruned += $orphanCount;
        }

        $resolvedAlertsPruned = $this->pruneResolvedAlerts($dryRun);

        $this->newLine();

        if ($dryRun) {
            $this->info("Dry-run complete. {$totalEntriesPruned} log entries would be deleted.");
            $this->info("Dry-run complete. {$resolvedAlertsPruned} resolved alerts would be deleted.");
        } else {
            $this->info("Prune complete. {$totalEntriesPruned} log entries deleted.");
            $this->info("Prune complete. {$resolvedAlertsPruned} resolved alerts deleted.");
        }

        return self::SUCCESS;
    }

    private function pruneEntriesForSource(
        LogSource $source,
        int $retentionDays,
        bool $dryRun,
        bool $includeOpenAlertEntries
    ): int {
        $cutoff = now()->subDays($retentionDays);

        $query = LogEntry::query()
            ->where('source_id', $source->id)
            ->where('occurred_at', '<', $cutoff);

        if (! $includeOpenAlertEntries && config('log-sentinel.retention.keep_entries_with_open_alerts', true)) {
            $query->whereDoesntHave('alerts', function ($alertQuery) {
                $alertQuery->whereIn('status', ['open', 'acknowledged']);
            });
        }

        $count = (clone $query)->count();

        $this->line(
            "Source [{$source->name}] retention {$retentionDays} days: {$count} old entries found."
        );

        if ($dryRun || $count === 0) {
            return $count;
        }

        $this->deleteInChunks($query);

        return $count;
    }

    private function pruneOrphanEntries(
        int $retentionDays,
        bool $dryRun,
        bool $includeOpenAlertEntries
    ): int {
        $cutoff = now()->subDays($retentionDays);

        $query = LogEntry::query()
            ->whereNull('source_id')
            ->where('occurred_at', '<', $cutoff);

        if (! $includeOpenAlertEntries && config('log-sentinel.retention.keep_entries_with_open_alerts', true)) {
            $query->whereDoesntHave('alerts', function ($alertQuery) {
                $alertQuery->whereIn('status', ['open', 'acknowledged']);
            });
        }

        $count = (clone $query)->count();

        $this->line("Orphan entries retention {$retentionDays} days: {$count} old entries found.");

        if ($dryRun || $count === 0) {
            return $count;
        }

        $this->deleteInChunks($query);

        return $count;
    }

    private function pruneResolvedAlerts(bool $dryRun): int
    {
        $retentionDays = (int) config('log-sentinel.retention.resolved_alert_days', 90);

        $cutoff = now()->subDays($retentionDays);

        $query = SecurityAlert::query()
            ->whereIn('status', ['resolved', 'ignored'])
            ->where('updated_at', '<', $cutoff);

        $count = (clone $query)->count();

        $this->line("Resolved alert retention {$retentionDays} days: {$count} old alerts found.");

        if ($dryRun || $count === 0) {
            return $count;
        }

        $query->delete();

        return $count;
    }

    private function deleteInChunks($query): void
    {
        $chunkSize = (int) config('log-sentinel.retention.prune_chunk_size', 500);

        $query->select('id')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($entries) {
                LogEntry::query()
                    ->whereIn('id', $entries->pluck('id'))
                    ->delete();
            });
    }
}
