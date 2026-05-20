@extends('log-sentinel::layouts.app')

@section('title', 'Logs')

@section('content')
    <div class="card">
        <h1>Log Entries</h1>
        <p>Search, sort, filter, and page through imported log entries.</p>
    </div>

    <div class="card">
        <h2>Filters</h2>

        <div class="form-grid">
            <div class="field">
                <label for="source-id-filter">Source</label>
                <select id="source-id-filter">
                    <option value="">All sources</option>
                    @foreach($sources as $source)
                        <option value="{{ $source->id }}">
                            {{ $source->name }} — {{ $source->type }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="source-type-filter">Source Type</label>
                <select id="source-type-filter">
                    <option value="">All source types</option>
                    @foreach($sourceTypes as $sourceType)
                        <option value="{{ $sourceType }}">{{ $sourceType }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="level-filter">Level</label>
                <select id="level-filter">
                    <option value="">All levels</option>
                    @foreach($levels as $level)
                        <option value="{{ $level }}">{{ strtoupper($level) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="status-code-filter">Status Code</label>
                <select id="status-code-filter">
                    <option value="">All statuses</option>
                    @foreach($statusCodes as $statusCode)
                        <option value="{{ $statusCode }}">{{ $statusCode }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="date-from-filter">Date From</label>
                <input type="date" id="date-from-filter">
            </div>

            <div class="field">
                <label for="date-to-filter">Date To</label>
                <input type="date" id="date-to-filter">
            </div>

            <div class="field" style="justify-content: end;">
                <label>&nbsp;</label>
                <button type="button" id="clear-log-filters" class="button-secondary">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <table id="logs-table" class="display">
            <thead>
            <tr>
                <th>Date</th>
                <th>Level</th>
                <th>Source</th>
                <th>Status</th>
                <th>IP</th>
                <th>Method</th>
                <th>URL</th>
                <th>Message</th>
                <th>Actions</th>
            </tr>
            </thead>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const logsTable = $('#logs-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('log-sentinel.logs.data') }}',
                    data: function (data) {
                        data.source_id = $('#source-id-filter').val();
                        data.source_type = $('#source-type-filter').val();
                        data.level = $('#level-filter').val();
                        data.status_code = $('#status-code-filter').val();
                        data.date_from = $('#date-from-filter').val();
                        data.date_to = $('#date-to-filter').val();
                    }
                },
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                columns: [
                    { data: 'date', name: 'occurred_at' },
                    { data: 'level', name: 'level', orderable: true },
                    { data: 'source_type', name: 'source_type' },
                    { data: 'status_code', name: 'status_code' },
                    { data: 'ip_address', name: 'ip_address' },
                    { data: 'method', name: 'method' },
                    { data: 'url', name: 'url' },
                    { data: 'message', name: 'message' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });

            $('#source-id-filter, #source-type-filter, #level-filter, #status-code-filter, #date-from-filter, #date-to-filter').on('change', function () {
                logsTable.ajax.reload();
            });

            $('#clear-log-filters').on('click', function () {
                $('#source-id-filter').val('');
                $('#source-type-filter').val('');
                $('#level-filter').val('');
                $('#status-code-filter').val('');
                $('#date-from-filter').val('');
                $('#date-to-filter').val('');

                logsTable.ajax.reload();
            });
        });
    </script>
@endpush
