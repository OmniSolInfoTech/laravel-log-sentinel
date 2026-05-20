@extends('log-sentinel::layouts.app')

@section('title', 'Alert Details')

@section('content')
    <div class="card">
        <h1>{{ $alert->title }}</h1>
        <p>{{ $alert->description }}</p>

        @if($alert->status === 'open')
            <form method="POST" action="{{ route('log-sentinel.alerts.acknowledge', $alert) }}" class="inline-form">
                @csrf
                <button type="submit" class="button-warning">Acknowledge</button>
            </form>
        @endif

        @if(in_array($alert->status, ['open', 'acknowledged'], true))
            <form method="POST" action="{{ route('log-sentinel.alerts.resolve', $alert) }}" class="inline-form">
                @csrf
                <button type="submit" class="button-success">Resolve</button>
            </form>
        @endif

        @if($alert->status === 'resolved')
            <form method="POST" action="{{ route('log-sentinel.alerts.reopen', $alert) }}" class="inline-form">
                @csrf
                <button type="submit" class="button-secondary">Reopen</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h2>Alert Details</h2>

        <div class="grid-2">
            <div class="field">
                <label>Severity</label>
                <div>{{ $alert->severity }}</div>
            </div>

            <div class="field">
                <label>Status</label>
                <div>{{ $alert->status }}</div>
            </div>

            <div class="field">
                <label>Type</label>
                <div>{{ $alert->type }}</div>
            </div>

            <div class="field">
                <label>IP Address</label>
                <div>{{ $alert->ip_address ?? '-' }}</div>
            </div>

            <div class="field">
                <label>Occurrences</label>
                <div>{{ $alert->occurrence_count }}</div>
            </div>

            <div class="field">
                <label>First Seen</label>
                <div>{{ optional($alert->first_seen_at)->format('Y-m-d H:i:s') ?? '-' }}</div>
            </div>

            <div class="field">
                <label>Last Seen</label>
                <div>{{ optional($alert->last_seen_at)->format('Y-m-d H:i:s') ?? '-' }}</div>
            </div>

            <div class="field">
                <label>Source</label>
                <div>{{ optional($alert->source)->name ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Alert Meta</h2>
        <pre>{{ json_encode($alert->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>

    @if($alert->entry)
        <div class="card">
            <h2>Linked Log Entry</h2>
            <p>
                <a href="{{ route('log-sentinel.logs.show', $alert->entry) }}" class="button">
                    View Linked Log Entry
                </a>
            </p>
            <pre>{{ $alert->entry->message }}</pre>
        </div>
    @endif
@endsection
