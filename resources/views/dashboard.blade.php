@extends('log-sentinel::layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="header-card">
        <span class="badge badge-on">Package Loaded</span>

        <h1>Laravel Log Sentinel</h1>

        <p>
            A live overview of log sources, imported entries, generated alerts, and suspicious activity.
        </p>

        <p>
            <a href="{{ route('log-sentinel.logs.index') }}" class="button">View Logs</a>
            <a href="{{ route('log-sentinel.sources.index') }}" class="button button-success">Manage Sources</a>
            <a href="{{ route('log-sentinel.alerts.index') }}" class="button button-warning">View Alerts</a>
        </p>
    </div>

    <div class="grid">
        <div class="metric">
            <span>Total Sources</span>
            <strong>{{ $sourceCount }}</strong>
            <small>{{ $activeSourceCount }} active</small>
        </div>

        <div class="metric">
            <span>Total Entries</span>
            <strong>{{ $entryCount }}</strong>
            <small>{{ $entriesToday }} today</small>
        </div>

        <div class="metric">
            <span>Error Entries</span>
            <strong>{{ $errorCount }}</strong>
            <small>{{ $errorsToday }} today</small>
        </div>

        <div class="metric">
            <span>Open Alerts</span>
            <strong>{{ $alertCount }}</strong>
            <small>{{ $criticalOpenAlertCount }} critical open</small>
        </div>
    </div>

    <div class="grid">
        <div class="metric">
            <span>Alerts Today</span>
            <strong>{{ $alertsToday }}</strong>
            <small>Created since midnight</small>
        </div>

        <div class="metric">
            <span>Critical Open</span>
            <strong>{{ $criticalOpenAlertCount }}</strong>
            <small>Needs urgent review</small>
        </div>

        <div class="metric">
            <span>Active Sources</span>
            <strong>{{ $activeSourceCount }}</strong>
            <small>Currently enabled</small>
        </div>

        <div class="metric">
            <span>Errors Today</span>
            <strong>{{ $errorsToday }}</strong>
            <small>Application/server issues</small>
        </div>
    </div>

    <div class="card">
        <h2>Activity: Last 24 Hours</h2>

        <div class="activity-chart">
            @php
                $maxActivity = max($activityByHour ?: [0]);
            @endphp

            @foreach($activityByHour as $hour => $total)
                @php
                    $height = $maxActivity > 0 ? max(8, ($total / $maxActivity) * 120) : 8;
                @endphp

                <div class="activity-bar-wrap" title="{{ $hour }} - {{ $total }} entries">
                    <div class="activity-bar" style="height: {{ $height }}px;"></div>
                    <small>{{ $hour }}</small>
                </div>
            @endforeach
        </div>
    </div>

    <div class="analytics-grid">
        <div class="card">
            <h2>Alerts by Severity</h2>

            <table>
                <thead>
                <tr>
                    <th>Severity</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($alertsBySeverity as $row)
                    <tr>
                        <td>
                            <span class="badge severity-{{ $row->severity }}">
                                {{ $row->severity }}
                            </span>
                        </td>
                        <td>{{ $row->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No alert severity data yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Alerts by Status</h2>

            <table>
                <thead>
                <tr>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($alertsByStatus as $row)
                    <tr>
                        <td>
                            <span class="badge status-{{ $row->status }}">
                                {{ $row->status }}
                            </span>
                        </td>
                        <td>{{ $row->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No alert status data yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="analytics-grid">
        <div class="card">
            <h2>Top Alert Types</h2>

            <table>
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($topAlertTypes as $row)
                    <tr>
                        <td>{{ $row->type }}</td>
                        <td>{{ $row->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No alert type data yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Top IP Addresses</h2>

            <table>
                <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Entries</th>
                </tr>
                </thead>
                <tbody>
                @forelse($topIpAddresses as $row)
                    <tr>
                        <td>{{ $row->ip_address }}</td>
                        <td>{{ $row->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No IP address data yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="analytics-grid">
        <div class="card">
            <h2>Latest Alerts</h2>

            <table>
                <thead>
                <tr>
                    <th>Severity</th>
                    <th>Title</th>
                    <th>Last Seen</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($latestAlerts as $alert)
                    <tr>
                        <td>
                            <span class="badge severity-{{ $alert->severity }}">
                                {{ $alert->severity }}
                            </span>
                        </td>
                        <td>{{ $alert->title }}</td>
                        <td>{{ optional($alert->last_seen_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('log-sentinel.alerts.show', $alert) }}" class="button">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No alerts created yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Latest Log Entries</h2>

            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Level</th>
                    <th>Source</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                @forelse($latestEntries as $entry)
                    <tr>
                        <td>{{ optional($entry->occurred_at)->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <span class="level level-{{ $entry->level }}">
                                {{ $entry->level ?? 'unknown' }}
                            </span>
                        </td>
                        <td>{{ $entry->source_type }}</td>
                        <td class="message">{{ $entry->message }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No log entries imported yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
        }

        .activity-chart {
            display: flex;
            align-items: end;
            gap: 8px;
            min-height: 170px;
            overflow-x: auto;
            padding-top: 20px;
        }

        .activity-bar-wrap {
            min-width: 42px;
            text-align: center;
        }

        .activity-bar {
            width: 24px;
            margin: 0 auto 8px;
            border-radius: 8px 8px 0 0;
            background: #111827;
        }

        .activity-bar-wrap small {
            font-size: 10px;
            color: #6b7280;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
        }

        @media (max-width: 900px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
