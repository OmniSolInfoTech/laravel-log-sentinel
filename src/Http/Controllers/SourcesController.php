<?php

namespace Osit\LogSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Osit\LogSentinel\Models\LogSource;
use Osit\LogSentinel\Services\LogScanner;
use Osit\LogSentinel\Services\PathSecurity;

class SourcesController extends Controller
{
    public function __construct(
        protected PathSecurity $pathSecurity
    ) {
    }

    public function index()
    {
        return view('log-sentinel::sources.index', [
            'sources' => LogSource::query()
                ->latest()
                ->paginate(20),

            'sourceTypes' => config('log-sentinel.source_types', []),
            'activeParsers' => config('log-sentinel.active_parsers', []),
        ]);
    }

    public function create()
    {
        return view('log-sentinel::sources.create', [
            'source' => new LogSource(),
            'sourceTypes' => config('log-sentinel.source_types', []),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSource($request);

        if ($reason = $this->pathSecurity->reason($data['path'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'path' => $reason,
                ]);
        }

        $data['enabled'] = $request->boolean('enabled');
        $data['parser'] = $data['parser'] ?: $data['type'];

        LogSource::create($data);

        return redirect()
            ->route('log-sentinel.sources.index')
            ->with('success', 'Log source created successfully.');
    }

    public function edit(LogSource $source)
    {
        return view('log-sentinel::sources.edit', [
            'source' => $source,
            'sourceTypes' => config('log-sentinel.source_types', []),
        ]);
    }

    public function update(Request $request, LogSource $source)
    {
        $data = $this->validateSource($request);

        if ($reason = $this->pathSecurity->reason($data['path'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'path' => $reason,
                ]);
        }

        $data['enabled'] = $request->boolean('enabled');
        $data['parser'] = $data['parser'] ?: $data['type'];

        $source->update($data);

        return redirect()
            ->route('log-sentinel.sources.index')
            ->with('success', 'Log source updated successfully.');
    }

    public function destroy(LogSource $source)
    {
        $source->delete();

        return redirect()
            ->route('log-sentinel.sources.index')
            ->with('success', 'Log source deleted successfully.');
    }

    public function toggle(LogSource $source)
    {
        $source->update([
            'enabled' => ! $source->enabled,
        ]);

        return back()->with(
            'success',
            $source->enabled
                ? 'Log source enabled successfully.'
                : 'Log source disabled successfully.'
        );
    }

    public function test(LogSource $source)
    {
        if ($reason = $this->pathSecurity->reason($source->path)) {
            return back()->with('error', $reason);
        }

        if (! File::exists($source->path)) {
            return back()->with('error', 'The log file does not exist.');
        }

        if (! is_readable($source->path)) {
            return back()->with('error', 'The log file exists but is not readable by PHP.');
        }

        $size = File::size($source->path);

        return back()->with(
            'success',
            'Log file is readable. File size: ' . number_format($size) . ' bytes.'
        );
    }

    public function scan(LogSource $source, LogScanner $scanner)
    {
        $parser = $source->parser ?: $source->type;

        if (! in_array($parser, config('log-sentinel.active_parsers', []), true)) {
            return back()->with(
                'error',
                'This parser is not active yet. For now, only the Laravel parser can scan log entries.'
            );
        }

        $count = $scanner->scan($source);

        return back()->with(
            'success',
            "Scan complete. Imported {$count} new log entries."
        );
    }

    private function validateSource(Request $request): array
    {
        $sourceTypes = array_keys(config('log-sentinel.source_types', []));

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($sourceTypes)],
            'parser' => ['nullable', 'string', Rule::in($sourceTypes)],
            'path' => ['required', 'string', 'max:2000'],
            'scan_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);
    }
}
