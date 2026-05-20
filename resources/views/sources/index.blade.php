@extends('log-sentinel::layouts.app')

@section('title', 'Log Sources')

@section('content')
    <div class="card">
        <h1>Log Sources</h1>
        <p>Manage the log files that Log Sentinel should monitor.</p>

        <p>
            <a href="{{ route('log-sentinel.sources.create') }}" class="button button-success">
                Add Log Source
            </a>
        </p>
    </div>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Parser</th>
                <th>Path</th>
                <th>Status</th>
                <th>Last Scanned</th>
                <th>Retention</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($sources as $source)
                @php
                    $parser = $source->parser ?: $source->type;
                    $parserActive = in_array($parser, $activeParsers, true);
                @endphp

                <tr>
                    <td>{{ $source->name }}</td>
                    <td>{{ $sourceTypes[$source->type] ?? $source->type }}</td>
                    <td>
                        <span class="badge badge-parser">{{ $parser }}</span>

                        @unless($parserActive)
                            <br>
                            <small>Parser coming soon</small>
                        @endunless
                    </td>
                    <td class="path">{{ $source->path }}</td>
                    <td>
                        @if($source->enabled)
                            <span class="badge badge-on">Enabled</span>
                        @else
                            <span class="badge badge-off">Disabled</span>
                        @endif
                    </td>
                    <td>
                        {{ optional($source->last_scanned_at)->format('Y-m-d H:i:s') ?? 'Never' }}
                    </td>
                    <td>{{ $source->retention_days }} days</td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('log-sentinel.sources.edit', $source) }}" class="button">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('log-sentinel.sources.toggle', $source) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="button-secondary">
                                    {{ $source->enabled ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('log-sentinel.sources.test', $source) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="button-warning">
                                    Test
                                </button>
                            </form>

                            @if($parserActive)
                                <form method="POST" action="{{ route('log-sentinel.sources.scan', $source) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="button-success">
                                        Scan
                                    </button>
                                </form>
                            @endif

                            <form
                                method="POST"
                                action="{{ route('log-sentinel.sources.destroy', $source) }}"
                                class="inline-form"
                                data-confirm
                                data-confirm-title="Delete this log source?"
                                data-confirm-text="This will not delete the actual log file. Existing imported entries will remain."
                                data-confirm-button="Yes, delete source"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button-danger">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        No log sources have been added yet.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        {{ $sources->links('log-sentinel::partials.pagination') }}
    </div>
@endsection
