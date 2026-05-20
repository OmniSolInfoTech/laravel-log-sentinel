<?php

namespace Osit\LogSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Osit\LogSentinel\Models\LogEntry;
use Osit\LogSentinel\Models\LogSource;

class LogsController extends Controller
{
    public function index()
    {
        return view('log-sentinel::logs.index', [
            'sources' => LogSource::query()
                ->orderBy('name')
                ->get(['id', 'name', 'type']),

            'levels' => LogEntry::query()
                ->whereNotNull('level')
                ->distinct()
                ->orderBy('level')
                ->pluck('level'),

            'sourceTypes' => LogEntry::query()
                ->whereNotNull('source_type')
                ->distinct()
                ->orderBy('source_type')
                ->pluck('source_type'),

            'statusCodes' => LogEntry::query()
                ->whereNotNull('status_code')
                ->distinct()
                ->orderBy('status_code')
                ->pluck('status_code'),
        ]);
    }

    public function data(Request $request)
    {
        $columns = [
            0 => 'occurred_at',
            1 => 'level',
            2 => 'source_type',
            3 => 'status_code',
            4 => 'ip_address',
            5 => 'method',
            6 => 'url',
            7 => 'message',
        ];

        $baseQuery = LogEntry::query();

        $recordsTotal = (clone $baseQuery)->count();

        $query = LogEntry::query();

        if ($request->filled('source_id')) {
            $query->where('source_id', $request->get('source_id'));
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->get('source_type'));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->get('level'));
        }

        if ($request->filled('status_code')) {
            $query->where('status_code', (int) $request->get('status_code'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', $request->get('date_to'));
        }

        $search = trim((string) $request->input('search.value'));

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('message', 'like', "%{$search}%")
                    ->orWhere('exception_class', 'like', "%{$search}%")
                    ->orWhere('file', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('method', 'like', "%{$search}%")
                    ->orWhere('source_type', 'like', "%{$search}%")
                    ->orWhere('level', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $orderColumn = $columns[$orderColumnIndex] ?? 'occurred_at';

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);

        if ($length < 1 || $length > 100) {
            $length = 25;
        }

        $entries = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $entries->map(function (LogEntry $entry) {
            $level = $entry->level ?: 'unknown';

            return [
                'date' => optional($entry->occurred_at)->format('Y-m-d H:i:s') ?: '-',
                'level' => '<span class="level level-' . e($level) . '">' . e(strtoupper($level)) . '</span>',
                'source_type' => e($entry->source_type ?: '-'),
                'status_code' => e($entry->status_code ?: '-'),
                'ip_address' => e($entry->ip_address ?: '-'),
                'method' => e($entry->method ?: '-'),
                'url' => e(Str::limit($entry->url ?: '-', 80)),
                'message' => e(Str::limit($entry->message ?: '-', 160)),
                'actions' => '<a href="' . route('log-sentinel.logs.show', $entry) . '" class="button">View</a>',
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show(LogEntry $entry)
    {
        $entry->load('source', 'alerts');

        return view('log-sentinel::logs.show', [
            'entry' => $entry,
        ]);
    }
}
