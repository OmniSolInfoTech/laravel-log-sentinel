<?php

namespace Osit\LogSentinel\Parsers;

use Carbon\Carbon;
use Osit\LogSentinel\Contracts\LogParserInterface;

abstract class BaseSyslogParser implements LogParserInterface
{
    public function splitEntries(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));

        return array_values(array_filter(array_map('trim', $lines)));
    }

    protected function parseSyslogLine(string $entry): ?array
    {
        $entry = trim($entry);

        if ($entry === '') {
            return null;
        }

        $pattern = '/^(?<datetime>[A-Z][a-z]{2}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2})\s+(?<hostname>\S+)\s+(?<process>[^\[:]+)(?:\[(?<pid>\d+)\])?:\s+(?<message>.*)$/';

        if (! preg_match($pattern, $entry, $matches)) {
            return [
                'occurred_at' => now(),
                'hostname' => null,
                'process' => null,
                'pid' => null,
                'message' => $entry,
                'raw_unparsed' => true,
            ];
        }

        return [
            'occurred_at' => $this->parseDate($matches['datetime']),
            'hostname' => $matches['hostname'] ?? null,
            'process' => $matches['process'] ?? null,
            'pid' => $matches['pid'] ?? null,
            'message' => trim($matches['message'] ?? ''),
            'raw_unparsed' => false,
        ];
    }

    protected function parseDate(string $date): Carbon
    {
        $year = now()->year;

        try {
            return Carbon::createFromFormat('Y M j H:i:s', $year . ' ' . preg_replace('/\s+/', ' ', $date));
        } catch (\Throwable) {
            return now();
        }
    }

    protected function extractIp(string $message): ?string
    {
        if (preg_match('/from\s+(?<ip>\d{1,3}(?:\.\d{1,3}){3})/i', $message, $matches)) {
            return $matches['ip'];
        }

        if (preg_match('/rhost=(?<ip>\d{1,3}(?:\.\d{1,3}){3})/i', $message, $matches)) {
            return $matches['ip'];
        }

        if (preg_match('/(?<ip>\d{1,3}(?:\.\d{1,3}){3})/', $message, $matches)) {
            return $matches['ip'];
        }

        return null;
    }

    protected function extractUser(string $message): ?string
    {
        $patterns = [
            '/for invalid user\s+(?<user>\S+)/i',
            '/Failed password for\s+(?<user>\S+)/i',
            '/Accepted password for\s+(?<user>\S+)/i',
            '/Accepted publickey for\s+(?<user>\S+)/i',
            '/user=(?<user>\S+)/i',
            '/by\s+(?<user>\S+)\(uid=/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return trim($matches['user'], ' ,');
            }
        }

        return null;
    }

    protected function extractPort(string $message): ?int
    {
        if (preg_match('/port\s+(?<port>\d+)/i', $message, $matches)) {
            return (int) $matches['port'];
        }

        return null;
    }

    protected function levelFromMessage(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'critical'),
            str_contains($lower, 'panic'),
            str_contains($lower, 'segfault'),
            str_contains($lower, 'out of memory') => 'critical',

            str_contains($lower, 'error'),
            str_contains($lower, 'failed'),
            str_contains($lower, 'failure'),
            str_contains($lower, 'denied'),
            str_contains($lower, 'refused') => 'error',

            str_contains($lower, 'warning'),
            str_contains($lower, 'warn') => 'warning',

            str_contains($lower, 'notice') => 'notice',

            default => 'info',
        };
    }
}
