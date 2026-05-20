<?php

namespace Osit\LogSentinel\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Osit\LogSentinel\Models\LogEntry;
use Osit\LogSentinel\Models\LogSource;
use Osit\LogSentinel\Models\SecurityAlert;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $last24Hours = now()->subHours(24);

        return view('log-sentinel::dashboard', [
            'sourceCount' => LogSource::count(),
            'activeSourceCount' => LogSource::where('enabled', true)->count(),

            'entryCount' => LogEntry::count(),
            'entriesToday' => LogEntry::where('occurred_at', '>=', $today)->count(),

            'errorCount' => LogEntry::whereIn('level', ['error', 'critical', 'alert', 'emergency'])->count(),
            'errorsToday' => LogEntry::whereIn('level', ['error', 'critical', 'alert', 'emergency'])
                ->where('occurred_at', '>=', $today)
                ->count(),

            'alertCount' => SecurityAlert::where('status', 'open')->count(),
            'criticalOpenAlertCount' => SecurityAlert::where('status', 'open')
                ->where('severity', 'critical')
                ->count(),

            'alertsToday' => SecurityAlert::where('created_at', '>=', $today)->count(),

            'latestEntries' => LogEntry::query()
                ->latest('occurred_at')
                ->limit(8)
                ->get(),

            'latestAlerts' => SecurityAlert::query()
                ->latest('last_seen_at')
                ->limit(8)
                ->get(),

            'alertsBySeverity' => $this->alertsBy('severity'),
            'alertsByStatus' => $this->alertsBy('status'),
            'topAlertTypes' => $this->topAlertTypes(),
            'topIpAddresses' => $this->topIpAddresses(),

            'activityByHour' => $this->activityByHour($last24Hours),
        ]);
    }

    private function alertsBy(string $column): Collection
    {
        return SecurityAlert::query()
            ->selectRaw("{$column}, COUNT(*) as total")
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderByDesc('total')
            ->get();
    }

    private function topAlertTypes(): Collection
    {
        return SecurityAlert::query()
            ->selectRaw('type, COUNT(*) as total')
            ->whereNotNull('type')
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
    }

    private function topIpAddresses(): Collection
    {
        return LogEntry::query()
            ->selectRaw('ip_address, COUNT(*) as total')
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '')
            ->groupBy('ip_address')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
    }

    private function activityByHour($from): array
    {
        $entries = LogEntry::query()
            ->where('occurred_at', '>=', $from)
            ->get(['occurred_at']);

        $hours = [];

        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('H:00');

            $hours[$hour] = 0;
        }

        foreach ($entries as $entry) {
            if (! $entry->occurred_at) {
                continue;
            }

            $hour = $entry->occurred_at->format('H:00');

            if (array_key_exists($hour, $hours)) {
                $hours[$hour]++;
            }
        }

        return $hours;
    }
}
