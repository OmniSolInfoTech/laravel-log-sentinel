<?php

namespace Osit\LogSentinel\Parsers;

use Osit\LogSentinel\Contracts\LogParserInterface;
use Carbon\Carbon;

class LaravelLogParser implements LogParserInterface
{
    public function parse(string $entry): ?array
    {
        $entry = trim($entry);

        if ($entry === '') {
            return null;
        }

        $pattern = '/^\[(?<datetime>.*?)\]\s+(?<environment>[a-zA-Z0-9_\-]+)\.(?<level>[A-Z]+):\s+(?<message>.*)$/s';

        if (! preg_match($pattern, $entry, $matches)) {
            return [
                'occurred_at' => now(),
                'level' => 'unknown',
                'message' => $entry,
                'context' => [
                    'raw' => true,
                ],
                'exception_class' => null,
                'file' => null,
                'line' => null,
            ];
        }

        $message = trim($matches['message']);
        $exceptionClass = $this->extractExceptionClass($message);
        [$file, $line] = $this->extractFileAndLine($message);

        return [
            'occurred_at' => $this->parseDate($matches['datetime']),
            'level' => strtolower($matches['level']),
            'message' => $message,
            'context' => [
                'environment' => $matches['environment'],
            ],
            'exception_class' => $exceptionClass,
            'file' => $file,
            'line' => $line,
        ];
    }

    private function parseDate(string $date): Carbon
    {
        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return now();
        }
    }

    private function extractExceptionClass(string $message): ?string
    {
        if (preg_match('/([A-Za-z0-9_\\\\]+Exception)/', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractFileAndLine(string $message): array
    {
        if (preg_match('/\sin\s(?<file>[A-Z]:\\\\.*?|\/.*?)\:(?<line>\d+)/', $message, $matches)) {
            return [
                $matches['file'],
                (int) $matches['line'],
            ];
        }

        return [null, null];
    }

    public function splitEntries(string $contents): array
    {
        $contents = trim($contents);

        if ($contents === '') {
            return [];
        }

        $parts = preg_split('/(?=^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/m', $contents);

        return array_values(array_filter(array_map('trim', $parts)));
    }
}
