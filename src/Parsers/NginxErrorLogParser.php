<?php

namespace Osit\LogSentinel\Parsers;

use Carbon\Carbon;
use Osit\LogSentinel\Contracts\LogParserInterface;

class NginxErrorLogParser implements LogParserInterface
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

        $pattern = '/^(?<datetime>\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s+\[(?<level>[a-z]+)\]\s+(?<pid>\d+)#(?<tid>\d+):\s+\*(?<connection>\d+)\s+(?<message>.*)$/i';

        if (! preg_match($pattern, $entry, $matches)) {
            return [
                'occurred_at' => now(),
                'level' => 'unknown',
                'message' => $entry,
                'context' => [
                    'raw' => $entry,
                    'raw_unparsed' => true,
                    'parser' => 'nginx_error',
                ],
            ];
        }

        $message = trim($matches['message'] ?? $entry);

        return [
            'occurred_at' => $this->parseDate($matches['datetime']),
            'level' => $this->normalizeLevel($matches['level'] ?? null),
            'message' => $message,
            'context' => [
                'parser' => 'nginx_error',
                'pid' => $matches['pid'] ?? null,
                'tid' => $matches['tid'] ?? null,
                'connection' => $matches['connection'] ?? null,
                'server' => $this->extractNamedValue($message, 'server'),
                'host' => $this->extractQuotedNamedValue($message, 'host'),
                'raw' => $entry,
            ],
            'ip_address' => $this->extractNamedValue($message, 'client'),
            'method' => $this->extractRequestPart($message, 'method'),
            'url' => $this->extractRequestPart($message, 'url'),
            'status_code' => null,
            'exception_class' => null,
            'file' => $this->extractFile($message),
            'line' => null,
        ];
    }

    private function parseDate(string $date): Carbon
    {
        try {
            return Carbon::createFromFormat('Y/m/d H:i:s', $date);
        } catch (\Throwable) {
            return now();
        }
    }

    private function normalizeLevel(?string $level): string
    {
        $level = strtolower(trim((string) $level));

        return match ($level) {
            'crit' => 'critical',
            'warn' => 'warning',
            'error' => 'error',
            'notice' => 'notice',
            'info' => 'info',
            'debug' => 'debug',
            default => $level ?: 'unknown',
        };
    }

    private function extractNamedValue(string $message, string $name): ?string
    {
        if (preg_match('/' . preg_quote($name, '/') . ':\s*(?<value>[^,]+)/i', $message, $matches)) {
            return trim($matches['value'], ' "');
        }

        return null;
    }

    private function extractQuotedNamedValue(string $message, string $name): ?string
    {
        if (preg_match('/' . preg_quote($name, '/') . ':\s+"(?<value>[^"]+)"/i', $message, $matches)) {
            return trim($matches['value']);
        }

        return null;
    }

    private function extractRequestPart(string $message, string $part): ?string
    {
        if (! preg_match('/request:\s+"(?<method>[A-Z]+)\s+(?<url>.*?)\s+HTTP\/[0-9.]+"?/i', $message, $matches)) {
            return null;
        }

        return $matches[$part] ?? null;
    }

    private function extractFile(string $message): ?string
    {
        if (preg_match('/"(?<file>\/[^"]+)"/', $message, $matches)) {
            return $matches['file'];
        }

        return null;
    }
}
