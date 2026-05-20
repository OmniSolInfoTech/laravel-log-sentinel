<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>@yield('title', 'Laravel Log Sentinel') | Laravel Log Sentinel</title>

    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 40px;
            color: #111827;
        }

        .container {
            max-width: 1300px;
            margin: auto;
        }

        .card,
        .header-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        h1 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        h2 {
            margin-top: 0;
        }

        p {
            line-height: 1.5;
        }

        .nav {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .nav a {
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
            padding: 8px 10px;
            border-radius: 8px;
        }

        .nav a:hover {
            background: #dbeafe;
        }

        .button,
        button {
            display: inline-block;
            background: #111827;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 9px 12px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            margin: 2px;
        }

        .button:hover,
        button:hover {
            opacity: 0.92;
        }

        .button-secondary {
            background: #6b7280;
        }

        .button-success {
            background: #15803d;
        }

        .button-warning {
            background: #b45309;
        }

        .button-danger {
            background: #b91c1c;
        }

        .button-info {
            background: #2563eb;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background: #e5e7eb;
            color: #374151;
        }

        .badge-on,
        .status-resolved {
            background: #dcfce7;
            color: #166534;
        }

        .badge-off,
        .status-open {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-parser,
        .severity-low {
            background: #dbeafe;
            color: #1e40af;
        }

        .severity-critical {
            background: #7f1d1d;
            color: #ffffff;
        }

        .severity-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .severity-medium,
        .status-acknowledged {
            background: #fef3c7;
            color: #92400e;
        }

        .level {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: #e5e7eb;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .level-error,
        .level-critical,
        .level-alert,
        .level-emergency {
            background: #fee2e2;
            color: #991b1b;
        }

        .level-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .level-info,
        .level-debug,
        .level-notice {
            background: #dbeafe;
            color: #1e40af;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 20px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .metric {
            background: #ffffff;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 8px 18px rgba(0,0,0,0.06);
        }

        .metric span {
            color: #6b7280;
            font-size: 14px;
        }

        .metric strong {
            display: block;
            font-size: 30px;
            margin-top: 8px;
        }

        .field {
            background: #f9fafb;
            border-radius: 10px;
            padding: 14px;
        }

        .field label {
            display: block;
            color: #374151;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .field div {
            font-size: 14px;
            word-break: break-word;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-sizing: border-box;
        }

        small {
            margin-top: 6px;
            color: #6b7280;
        }

        .checkbox-field {
            display: flex;
            align-items: center;
        }

        .checkbox-field input {
            width: auto;
            margin-right: 8px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 13px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            padding: 10px;
        }

        td {
            border-bottom: 1px solid #f3f4f6;
            padding: 10px;
            vertical-align: top;
            font-size: 14px;
        }

        .path {
            max-width: 330px;
            word-break: break-word;
            color: #374151;
        }

        .message {
            max-width: 550px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .inline-form {
            display: inline;
        }

        pre {
            background: #111827;
            color: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        table.dataTable {
            width: 100% !important;
        }

        .dt-container .dt-search input,
        .dt-container .dt-length select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 7px 10px;
        }

        .dt-container .dt-paging .dt-paging-button {
            border-radius: 8px !important;
        }

        .ls-pagination-wrapper {
            margin-top: 20px;
        }

        .ls-pagination-info {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .ls-pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        .ls-page-link {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            color: #111827;
            text-decoration: none;
            font-size: 13px;
        }

        .ls-page-link:hover {
            background: #f3f4f6;
        }

        .ls-page-link.active {
            background: #111827;
            border-color: #111827;
            color: #ffffff;
        }

        .ls-page-link.disabled {
            color: #9ca3af;
            background: #f9fafb;
            cursor: not-allowed;
        }

        @media (max-width: 1000px) {
            .grid {
                grid-template-columns: 1fr 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            body {
                padding: 20px;
            }

            .grid,
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .card {
                overflow-x: auto;
            }

            .message {
                max-width: 260px;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="container">
    @include('log-sentinel::partials.nav')

    @yield('content')
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>

@include('log-sentinel::partials.toasts')

@stack('scripts')
</body>
</html>
