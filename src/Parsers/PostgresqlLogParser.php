<?php

namespace Osit\LogSentinel\Parsers;

use Carbon\Carbon;
use Osit\LogSentinel\Contracts\LogParserInterface;

class PostgresqlLogParser implements LogParserInterface
{
    public function splitEntries(string $contents): array
    {
        $contents = trim($contents);

        if ($contents === '') {
            return [];
        }

        $parts = preg_split('/(?=^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/m', $contents);

        return array_values(array_filter(array_map('trim', $parts)));
    }

    public function parse(string $entry): ?array
    {
        $entry = trim($entry);

        if ($entry === '') {
            return null;
        }

        $pattern = '/^(?<datetime>\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}(?:\.\d+)?)\s+(?<timezone>\S+)?\s*(?:\[(?<pid>\d+)\])?\s*(?:(?<user_database>\S+@\S+)\s+)?(?<level>[A-Z]+):\s+(?<message>.*)$/s';

        if (! preg_match($pattern, $entry, $matches)) {
            return [
                'occurred_at' => now(),
                'level' => $this->levelFromMessage($entry),
                'message' => $entry,
                'context' => [
                    'parser' => 'postgresql',
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

        $message = trim($matches['message'] ?? '');
        [$user, $database] = $this->extractUserDatabase($matches['user_database'] ?? null);

        return [
            'occurred_at' => $this->parseDate($matches['datetime']),
            'level' => $this->normalizeLevel($matches['level'] ?? null),
            'message' => $message,
            'context' => [
                'parser' => 'postgresql',
                'timezone' => $matches['timezone'] ?? null,
                'pid' => $matches['pid'] ?? null,
                'user' => $user,
                'database' => $database,
                'database_event' => $this->databaseEvent($message),
                'sql_state' => $this->extractSqlState($message),
                'raw' => $entry,
            ],
            'ip_address' => $this->extractIp($message),
            'method' => null,
            'url' => null,
            'status_code' => null,
            'exception_class' => null,
            'file' => null,
            'line' => $this->extractLine($message),
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
        $level = strtoupper(trim((string) $level));

        return match ($level) {
            'PANIC' => 'critical',
            'FATAL' => 'critical',
            'ERROR' => 'error',
            'WARNING' => 'warning',
            'NOTICE' => 'notice',
            'LOG' => 'info',
            'INFO' => 'info',
            'DEBUG' => 'debug',
            default => strtolower($level) ?: 'unknown',
        };
    }

    private function levelFromMessage(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'panic'),
            str_contains($lower, 'fatal') => 'critical',

            str_contains($lower, 'error'),
            str_contains($lower, 'failed'),
            str_contains($lower, 'does not exist') => 'error',

            str_contains($lower, 'warning') => 'warning',

            default => 'info',
        };
    }

    private function extractUserDatabase(?string $value): array
    {
        if (! $value || ! str_contains($value, '@')) {
            return [null, null];
        }

        [$user, $database] = explode('@', $value, 2);

        return [$user ?: null, $database ?: null];
    }

    private function databaseEvent(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'password authentication failed') => 'auth_failed',
            str_contains($lower, 'role') && str_contains($lower, 'does not exist') => 'role_missing',
            str_contains($lower, 'database') && str_contains($lower, 'does not exist') => 'database_missing',
            str_contains($lower, 'relation') && str_contains($lower, 'does not exist') => 'relation_missing',
            str_contains($lower, 'deadlock detected') => 'deadlock',
            str_contains($lower, 'could not connect') => 'connection_failed',
            str_contains($lower, 'ready to accept connections') => 'ready',
            str_contains($lower, 'checkpoint') => 'checkpoint',
            default => 'postgresql',
        };
    }

    private function extractSqlState(string $message): ?string
    {
        if (preg_match('/SQLSTATE\[(?<state>[A-Z0-9]+)\]/i', $message, $matches)) {
            return $matches['state'];
        }

        return null;
    }

    private function extractLine(string $message): ?int
    {
        if (preg_match('/line\s+(?<line>\d+)/i', $message, $matches)) {
            return (int) $matches['line'];
        }

        return null;
    }

    private function extractIp(string $message): ?string
    {
        if (preg_match('/(?<ip>\d{1,3}(?:\.\d{1,3}){3})/', $message, $matches)) {
            return $matches['ip'];
        }

        return null;
    }
}
