<?php

namespace Osit\LogSentinel\Services;

use Illuminate\Support\Facades\File;
use Osit\LogSentinel\Models\LogEntry;
use Osit\LogSentinel\Models\LogSource;

class LogScanner
{
    public function __construct(
        protected ParserResolver $parserResolver,
        protected AlertDetector $alertDetector,
        protected PathSecurity $pathSecurity,
        protected Redactor $redactor
    ) {
    }

    public function scan(?LogSource $source = null): int
    {
        if ($source) {
            return $this->scanSource($source);
        }

        return LogSource::query()
            ->where('enabled', true)
            ->get()
            ->sum(fn (LogSource $source) => $this->scanSource($source));
    }

    public function scanSource(LogSource $source): int
    {
        if (! $this->pathSecurity->isAllowed($source->path)) {
            return 0;
        }

        if (! File::exists($source->path)) {
            return 0;
        }

        $parser = $this->parserResolver->resolve($source);

        if (! $parser) {
            return 0;
        }

        $contents = File::get($source->path);

        $entries = $parser->splitEntries($contents);

        $count = 0;

        foreach ($entries as $rawEntry) {
            $parsed = $parser->parse($rawEntry);

            if (! $parsed) {
                continue;
            }

            $parsed = $this->redactor->redactArray($parsed);

            $hash = sha1(
                $source->id . '|' .
                ($parsed['occurred_at'] ?? '') . '|' .
                ($parsed['level'] ?? '') . '|' .
                ($parsed['message'] ?? '')
            );

            $exists = LogEntry::query()
                ->where('hash', $hash)
                ->exists();

            if ($exists) {
                continue;
            }

            $entry = LogEntry::create([
                'source_id' => $source->id,
                'source_type' => $source->type,
                'level' => $parsed['level'] ?? null,
                'message' => $parsed['message'] ?? '',
                'context' => $parsed['context'] ?? null,
                'ip_address' => $parsed['ip_address'] ?? null,
                'method' => $parsed['method'] ?? null,
                'url' => $parsed['url'] ?? null,
                'status_code' => $parsed['status_code'] ?? null,
                'user_id' => $parsed['user_id'] ?? null,
                'exception_class' => $parsed['exception_class'] ?? null,
                'file' => $parsed['file'] ?? null,
                'line' => $parsed['line'] ?? null,
                'occurred_at' => $parsed['occurred_at'] ?? now(),
                'hash' => $hash,
            ]);

            $this->alertDetector->detect($entry);

            $count++;
        }

        $source->update([
            'last_scanned_at' => now(),
            'last_position' => File::size($source->path),
        ]);

        return $count;
    }
}
