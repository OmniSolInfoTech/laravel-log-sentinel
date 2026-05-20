@extends('log-sentinel::layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="card">
        <h1>Settings</h1>
        <p>
            Review the current Laravel Log Sentinel configuration, active parsers, retention rules,
            security settings, and scheduler instructions.
        </p>
    </div>

    <div class="settings-grid">
        <div class="card">
            <h2>Package Information</h2>

            <table>
                <tbody>
                <tr>
                    <th>Name</th>
                    <td>{{ $package['name'] }}</td>
                </tr>
                <tr>
                    <th>Composer Package</th>
                    <td>{{ $package['composer_name'] }}</td>
                </tr>
                <tr>
                    <th>Version</th>
                    <td>{{ $package['version'] }}</td>
                </tr>
                <tr>
                    <th>PHP Version</th>
                    <td>{{ $package['php_version'] }}</td>
                </tr>
                <tr>
                    <th>Laravel Version</th>
                    <td>{{ $package['laravel_version'] }}</td>
                </tr>
                <tr>
                    <th>Environment</th>
                    <td>{{ $package['environment'] }}</td>
                </tr>
                <tr>
                    <th>Debug Enabled</th>
                    <td>
                        @if($package['debug_enabled'])
                            <span class="badge severity-medium">Yes</span>
                        @else
                            <span class="badge badge-on">No</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Timezone</th>
                    <td>{{ $package['timezone'] }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Route & Middleware</h2>

            <table>
                <tbody>
                <tr>
                    <th>Route Prefix</th>
                    <td>
                        <code>/{{ $routePrefix }}</code>
                    </td>
                </tr>
                <tr>
                    <th>Middleware</th>
                    <td>
                        @forelse($middleware as $item)
                            <span class="badge badge-parser">{{ $item }}</span>
                        @empty
                            <span class="badge severity-medium">None configured</span>
                        @endforelse
                    </td>
                </tr>
                </tbody>
            </table>

            <p>
                Before production, this should normally include authentication middleware, for example:
            </p>

            <pre>'middleware' => ['web', 'auth']</pre>
        </div>
    </div>

    <div class="settings-grid">
        <div class="card">
            <h2>Active Parsers</h2>

            <table>
                <thead>
                <tr>
                    <th>Parser</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sourceTypes as $key => $label)
                    <tr>
                        <td>
                            <strong>{{ $key }}</strong><br>
                            <small>{{ $label }}</small>
                        </td>
                        <td>
                            @if(in_array($key, $activeParsers, true))
                                <span class="badge badge-on">Active</span>
                            @else
                                <span class="badge severity-medium">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No source types configured.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Path Security</h2>

            <table>
                <tbody>
                <tr>
                    <th>Restrict Paths</th>
                    <td>
                        @if($pathSecurity['allow_only_configured_paths'] ?? true)
                            <span class="badge badge-on">Enabled</span>
                        @else
                            <span class="badge severity-high">Disabled</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Allowed Base Paths</th>
                    <td>
                        @forelse(($pathSecurity['allowed_base_paths'] ?? []) as $path)
                            <div><code>{{ $path }}</code></div>
                        @empty
                            <span class="badge severity-high">No allowed paths configured</span>
                        @endforelse
                    </td>
                </tr>
                </tbody>
            </table>

            <p>
                Log Sentinel should only read configured paths. Avoid allowing arbitrary file paths in production.
            </p>
        </div>
    </div>
    <div class="card">
        <h2>Redaction</h2>

        <table>
            <tbody>
            <tr>
                <th>Enabled</th>
                <td>
                    @if($redaction['enabled'] ?? true)
                        <span class="badge badge-on">Enabled</span>
                    @else
                        <span class="badge severity-high">Disabled</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Mask</th>
                <td><code>{{ $redaction['mask'] ?? '[REDACTED]' }}</code></td>
            </tr>
            <tr>
                <th>Mask Emails</th>
                <td>
                    @if($redaction['mask_emails'] ?? false)
                        <span class="badge badge-on">Yes</span>
                    @else
                        <span class="badge severity-low">No</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Sensitive Keys</th>
                <td>
                    @forelse(($redaction['sensitive_keys'] ?? []) as $key)
                        <span class="badge badge-parser">{{ $key }}</span>
                    @empty
                        <span class="badge severity-medium">No keys configured</span>
                    @endforelse
                </td>
            </tr>
            </tbody>
        </table>

        <p>
            Redaction is applied before parsed log entries are stored in the database.
        </p>
    </div>
    <div class="settings-grid">
        <div class="card">
            <h2>Retention Settings</h2>

            <table>
                <tbody>
                <tr>
                    <th>Default Log Days</th>
                    <td>{{ $retention['default_log_days'] ?? 30 }}</td>
                </tr>
                <tr>
                    <th>Resolved Alert Days</th>
                    <td>{{ $retention['resolved_alert_days'] ?? 90 }}</td>
                </tr>
                <tr>
                    <th>Keep Open Alert Entries</th>
                    <td>
                        @if($retention['keep_entries_with_open_alerts'] ?? true)
                            <span class="badge badge-on">Yes</span>
                        @else
                            <span class="badge severity-high">No</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Prune Chunk Size</th>
                    <td>{{ $retention['prune_chunk_size'] ?? 500 }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Useful Commands</h2>

            @foreach($commands as $label => $command)
                <div class="command-row">
                    <label>{{ $label }}</label>
                    <pre>{{ $command }}</pre>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h2>Scheduler Setup</h2>

        <p>
            Add these to the host Laravel application’s scheduler, usually in:
        </p>

        <pre>routes/console.php</pre>

        @foreach($schedulerExamples as $example)
            <pre>{{ $example }}</pre>
        @endforeach

        <p>
            The scan command imports new log entries. The prune command removes old records based on the retention rules.
        </p>
    </div>

    <div class="card">
        <h2>Production Checklist</h2>

        <table>
            <thead>
            <tr>
                <th>Check</th>
                <th>Recommendation</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Authentication</td>
                <td>Use <code>auth</code> middleware or your admin middleware before production.</td>
            </tr>
            <tr>
                <td>Path Security</td>
                <td>Keep <code>allow_only_configured_paths</code> enabled.</td>
            </tr>
            <tr>
                <td>Scheduler</td>
                <td>Run scan every 5 minutes and prune daily.</td>
            </tr>
            <tr>
                <td>Retention</td>
                <td>Keep log entries for only as long as needed.</td>
            </tr>
            <tr>
                <td>Debug Mode</td>
                <td>Disable Laravel debug mode in production.</td>
            </tr>
            </tbody>
        </table>
    </div>
@endsection

@push('styles')
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
        }

        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 13px;
        }

        .command-row {
            margin-bottom: 16px;
        }

        .command-row label {
            display: block;
            font-size: 13px;
            color: #374151;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .command-row pre {
            margin: 0;
        }

        @media (max-width: 900px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
