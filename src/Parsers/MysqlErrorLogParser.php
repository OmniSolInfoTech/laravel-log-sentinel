<?php

namespace Osit\LogSentinel\Parsers;

use Carbon\Carbon;
use Osit\LogSentinel\Contracts\LogParserInterface;

class MysqlErrorLogParser implements LogParserInterface
{
    public function splitEntries(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));

        return array_values(array_filter(array_map('trim', $lines)));
    }

    public function parse(string $entry): ?array
    {
        $entry = trim($entry);

        if ($entry === '') {
            return null;
        }

        $parsed = $this->parseModernMysql($entry)
            ?? $this->parseClassicMysql($entry)
            ?? $this->parseFallback($entry);

        return $parsed;
    }

    private function parseModernMysql(string $entry): ?array
    {
        $pattern = '/^(?<datetime>\d{4}-\d{2}-\d{2}T[^\s]+)\s+(?<thread>\d+)\s+\[(?<level>[^\]]+)\]\s+(?:\[(?<code>MY-\d+)\]\s+)?(?:\[(?<subsystem>[^\]]+)\]\s+)?(?<message>.*)$/';

        if (! preg_match($pattern, $entry, $matches)) {
            return null;
        }

        $message = trim($matches['message'] ?? '');

        return [
            'occurred_at' => $this->parseDate($matches['datetime']),
            'level' => $this->normalizeLevel($matches['level'] ?? null),
            'message' => $message,
            'context' => [
                'parser' => 'mysql_error',
                'format' => 'modern',
                'thread' => $matches['thread'] ?? null,
                'code' => $matches['code'] ?? null,
                'subsystem' => $matches['subsystem'] ?? null,
                'database_event' => $this->databaseEvent($message),
                'raw' => $entry,
            ],
            'ip_address' => $this->extractIp($message),
            'method' => null,
            'url' => null,
            'status_code' => null,
            'exception_class' => null,
            'file' => null,
            'line' => null,
        ];
    }

    private function parseClassicMysql(string $entry): ?array
    {
        $pattern = '/^(?<datetime>\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+(?<thread>\d+)?\s*\[(?<level>[^\]]+)\]\s+(?<message>.*)$/';

        if (! preg_match($pattern, $entry, $matches)) {
            return null;
        }

        $message = trim($matches['message'] ?? '');

        return [
            'occurred_at' => $this->parseDate($matches['datetime']),
            'level' => $this->normalizeLevel($matches['level'] ?? null),
            'message' => $message,
            'context' => [
                'parser' => 'mysql_error',
                'format' => 'classic',
                'thread' => $matches['thread'] ?? null,
                'database_event' => $this->databaseEvent($message),
                'raw' => $entry,
            ],
            'ip_address' => $this->extractIp($message),
            'method' => null,
            'url' => null,
            'status_code' => null,
            'exception_class' => null,
            'file' => null,
            'line' => null,
        ];
    }

    private function parseFallback(string $entry): array
    {
        return [
            'occurred_at' => now(),
            'level' => $this->levelFromMessage($entry),
            'message' => $entry,
            'context' => [
                'parser' => 'mysql_error',
                'format' => 'fallback',
                'raw_unparsed' => true,
                'database_event' => $this->databaseEvent($entry),
                'raw' => $entry,
            ],
            'ip_address' => $this->extractIp($entry),
            'method' => null,
            'url' => null,
            'status_code' => null,
            'exception_class' => null,
            'file' => null,
            'line' => null,
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

    private function normalizeLevel(?string $level): string
    {
        $level = strtolower(trim((string) $level));

        return match ($level) {
            'error', 'err' => 'error',
            'warning', 'warn', 'note' => 'warning',
            'system', 'information', 'info' => 'info',
            default => $level ?: 'unknown',
        };
    }

    private function levelFromMessage(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'fatal'),
            str_contains($lower, 'crash'),
            str_contains($lower, 'corrupt') => 'critical',

            str_contains($lower, 'error'),
            str_contains($lower, 'failed'),
            str_contains($lower, 'denied'),
            str_contains($lower, 'aborting') => 'error',

            str_contains($lower, 'warning'),
            str_contains($lower, 'deprecated') => 'warning',

            default => 'info',
        };
    }

    private function databaseEvent(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'access denied') => 'access_denied',
            str_contains($lower, 'unknown database') => 'unknown_database',
            str_contains($lower, 'too many connections') => 'too_many_connections',
            str_contains($lower, 'innodb') => 'innodb',
            str_contains($lower, 'deadlock') => 'deadlock',
            str_contains($lower, 'crash') => 'crash',
            str_contains($lower, 'shutdown') => 'shutdown',
            str_contains($lower, 'ready for connections') => 'ready',
            str_contains($lower, 'aborting') => 'aborting',
            default => 'mysql',
        };
    }

    private function extractIp(string $message): ?string
    {
        if (preg_match('/(?<ip>\d{1,3}(?:\.\d{1,3}){3})/', $message, $matches)) {
            return $matches['ip'];
        }

        return null;
    }
}
