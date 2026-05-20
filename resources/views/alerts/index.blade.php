@extends('log-sentinel::layouts.app')

@section('title', 'Alerts')

@section('content')
    <div class="card">
        <h1>Security Alerts</h1>
        <p>Search, filter, and manage alerts created automatically from parsed log activity.</p>
    </div>

    <div class="card">
        <div class="form-grid">
            <div class="field">
                <label for="severity-filter">Severity</label>
                <select id="severity-filter">
                    <option value="">All severities</option>
                    @foreach($severities as $severity)
                        <option value="{{ $severity }}">{{ ucfirst($severity) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="status-filter">Status</label>
                <select id="status-filter">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="type-filter">Type</label>
                <select id="type-filter">
                    <option value="">All types</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="justify-content: end;">
                <label>&nbsp;</label>
                <button type="button" id="clear-alert-filters" class="button-secondary">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <table id="alerts-table" class="display">
            <thead>
            <tr>
                <th>Severity</th>
                <th>Status</th>
                <th>Type</th>
                <th>Title</th>
                <th>IP</th>
                <th>Occurrences</th>
                <th>Last Seen</th>
                <th>Actions</th>
            </tr>
            </thead>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertsTable = $('#alerts-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('log-sentinel.alerts.data') }}',
                    data: function (data) {
                        data.severity = $('#severity-filter').val();
                        data.status = $('#status-filter').val();
                        data.type = $('#type-filter').val();
                    }
                },
                order: [[6, 'desc']],
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                columns: [
                    { data: 'severity', name: 'severity' },
                    { data: 'status', name: 'status' },
                    { data: 'type', name: 'type' },
                    { data: 'title', name: 'title' },
                    { data: 'ip_address', name: 'ip_address' },
                    { data: 'occurrence_count', name: 'occurrence_count' },
                    { data: 'last_seen_at', name: 'last_seen_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });

            $('#severity-filter, #status-filter, #type-filter').on('change', function () {
                alertsTable.ajax.reload();
            });

            $('#clear-alert-filters').on('click', function () {
                $('#severity-filter').val('');
                $('#status-filter').val('');
                $('#type-filter').val('');
                alertsTable.ajax.reload();
            });
        });
    </script>
@endpush
