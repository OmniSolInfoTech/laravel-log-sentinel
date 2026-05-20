<?php

namespace Osit\LogSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Osit\LogSentinel\Models\SecurityAlert;

class AlertsController extends Controller
{
    public function index()
    {
        return view('log-sentinel::alerts.index', [
            'severities' => SecurityAlert::query()
                ->whereNotNull('severity')
                ->distinct()
                ->orderBy('severity')
                ->pluck('severity'),

            'statuses' => SecurityAlert::query()
                ->whereNotNull('status')
                ->distinct()
                ->orderBy('status')
                ->pluck('status'),

            'types' => SecurityAlert::query()
                ->whereNotNull('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type'),
        ]);
    }

    public function data(Request $request)
    {
        $columns = [
            0 => 'severity',
            1 => 'status',
            2 => 'type',
            3 => 'title',
            4 => 'ip_address',
            5 => 'occurrence_count',
            6 => 'last_seen_at',
        ];

        $baseQuery = SecurityAlert::query();

        $recordsTotal = (clone $baseQuery)->count();

        $query = SecurityAlert::query();

        if ($request->filled('severity')) {
            $query->where('severity', $request->get('severity'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        $search = trim((string) $request->input('search.value'));

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('severity', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = (int) $request->input('order.0.column', 6);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $orderColumn = $columns[$orderColumnIndex] ?? 'last_seen_at';

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);

        if ($length < 1 || $length > 100) {
            $length = 25;
        }

        $alerts = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $alerts->map(function (SecurityAlert $alert) {
            return [
                'severity' => '<span class="badge severity-' . e($alert->severity) . '">' . e($alert->severity) . '</span>',
                'status' => '<span class="badge status-' . e($alert->status) . '">' . e($alert->status) . '</span>',
                'type' => e($alert->type),
                'title' => e(Str::limit($alert->title, 80)),
                'ip_address' => e($alert->ip_address ?: '-'),
                'occurrence_count' => e((string) $alert->occurrence_count),
                'last_seen_at' => optional($alert->last_seen_at)->format('Y-m-d H:i:s') ?: '-',
                'actions' => $this->actionsHtml($alert),
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show(SecurityAlert $alert)
    {
        $alert->load('source', 'entry');

        return view('log-sentinel::alerts.show', [
            'alert' => $alert,
        ]);
    }

    public function acknowledge(SecurityAlert $alert)
    {
        $alert->update([
            'status' => 'acknowledged',
        ]);

        return back()->with('success', 'Alert acknowledged.');
    }

    public function resolve(SecurityAlert $alert)
    {
        $alert->update([
            'status' => 'resolved',
        ]);

        return back()->with('success', 'Alert resolved.');
    }

    public function reopen(SecurityAlert $alert)
    {
        $alert->update([
            'status' => 'open',
        ]);

        return back()->with('success', 'Alert reopened.');
    }

    private function actionsHtml(SecurityAlert $alert): string
    {
        $html = '<div class="actions">';

        $html .= '<a href="' . route('log-sentinel.alerts.show', $alert) . '" class="button">View</a>';

        if ($alert->status === 'open') {
            $html .= $this->postButton(
                route('log-sentinel.alerts.acknowledge', $alert),
                'Acknowledge',
                'button-warning'
            );
        }

        if (in_array($alert->status, ['open', 'acknowledged'], true)) {
            $html .= $this->postButton(
                route('log-sentinel.alerts.resolve', $alert),
                'Resolve',
                'button-success'
            );
        }

        if ($alert->status === 'resolved') {
            $html .= $this->postButton(
                route('log-sentinel.alerts.reopen', $alert),
                'Reopen',
                'button-secondary'
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function postButton(string $action, string $label, string $class): string
    {
        return '
            <form method="POST" action="' . e($action) . '" class="inline-form">
                <input type="hidden" name="_token" value="' . e(csrf_token()) . '">
                <button type="submit" class="' . e($class) . '">' . e($label) . '</button>
            </form>
        ';
    }
}
