<?php

namespace Osit\LogSentinel\Parsers;

class LinuxSystemLogParser extends BaseSyslogParser
{
    public function parse(string $entry): ?array
    {
        $parsed = $this->parseSyslogLine($entry);

        if (! $parsed) {
            return null;
        }

        $message = $parsed['message'];

        return [
            'occurred_at' => $parsed['occurred_at'],
            'level' => $this->systemLevel($message, $parsed['process']),
            'message' => $message,
            'context' => [
                'parser' => 'linux_system',
                'hostname' => $parsed['hostname'],
                'process' => $parsed['process'],
                'pid' => $parsed['pid'],
                'event_type' => $this->eventType($message, $parsed['process']),
                'raw_unparsed' => $parsed['raw_unparsed'],
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

    private function systemLevel(string $message, ?string $process): string
    {
        $lower = strtolower($message . ' ' . $process);

        return match (true) {
            str_contains($lower, 'out of memory'),
            str_contains($lower, 'kernel panic'),
            str_contains($lower, 'segfault'),
            str_contains($lower, 'critical') => 'critical',

            str_contains($lower, 'failed'),
            str_contains($lower, 'failure'),
            str_contains($lower, 'error'),
            str_contains($lower, 'unable to'),
            str_contains($lower, 'denied') => 'error',

            str_contains($lower, 'warning'),
            str_contains($lower, 'warn') => 'warning',

            str_contains($lower, 'cron') => 'notice',

            default => 'info',
        };
    }

    private function eventType(string $message, ?string $process): string
    {
        $lower = strtolower($message . ' ' . $process);

        return match (true) {
            str_contains($lower, 'cron') => 'cron',
            str_contains($lower, 'kernel') => 'kernel',
            str_contains($lower, 'systemd') => 'systemd',
            str_contains($lower, 'started') => 'service_started',
            str_contains($lower, 'stopped') => 'service_stopped',
            str_contains($lower, 'failed') => 'service_failure',
            str_contains($lower, 'out of memory') => 'memory',
            str_contains($lower, 'disk') => 'disk',
            default => 'system',
        };
    }
}
