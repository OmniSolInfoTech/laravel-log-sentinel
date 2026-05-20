@extends('log-sentinel::layouts.app')

@section('title', 'Log Entry Details')

@section('content')
    <div class="card">
        <h1>Log Entry Details</h1>

        <div class="grid-2">
            <div class="field">
                <label>Date</label>
                <div>{{ optional($entry->occurred_at)->format('Y-m-d H:i:s') ?? '-' }}</div>
            </div>

            <div class="field">
                <label>Level</label>
                <div>
                    <span class="level level-{{ $entry->level }}">
                        {{ $entry->level ?? 'unknown' }}
                    </span>
                </div>
            </div>

            <div class="field">
                <label>Source Type</label>
                <div>{{ $entry->source_type ?? '-' }}</div>
            </div>

            <div class="field">
                <label>Source Name</label>
                <div>{{ optional($entry->source)->name ?? '-' }}</div>
            </div>

            <div class="field">
                <label>Status Code</label>
                <div>{{ $entry->status_code ?? '-' }}</div>
            </div>

            <div class="field">
                <label>IP Address</label>
                <div>{{ $entry->ip_address ?? '-' }}</div>
            </div>

            <div class="field">
                <label>Method</label>
                <div>{{ $entry->method ?? '-' }}</div>
            </div>

            <div class="field">
                <label>URL</label>
                <div>{{ $entry->url ?? '-' }}</div>
            </div>

            <div class="field">
                <label>Exception Class</label>
                <div>{{ $entry->exception_class ?? '-' }}</div>
            </div>

            <div class="field">
                <label>File / Line</label>
                <div>
                    {{ $entry->file ?? '-' }}
                    @if($entry->line)
                        :{{ $entry->line }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Message</h2>
        <pre>{{ $entry->message }}</pre>
    </div>

    <div class="card">
        <h2>Context</h2>
        <pre>{{ json_encode($entry->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
@endsection
