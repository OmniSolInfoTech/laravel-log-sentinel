<?php

namespace Osit\LogSentinel\Parsers;

use Carbon\Carbon;
use Osit\LogSentinel\Contracts\LogParserInterface;

class ApacheErrorLogParser implements LogParserInterface
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

        $pattern = '/^\[(?<datetime>[^\]]+)\]\s+\[(?<module_level>[^\]]+)\](?:\s+\[pid\s+(?<pid>[^\]]+)\])?(?:\s+\[client\s+(?<client>[^\]]+)\])?\s+(?<message>.*)$/';

        if (!preg_match($pattern, $entry, $matches)) {
            return [
                'occurred_at' => now(),
                'level' => 'unknown',
                'message' => $entry,
                'context' => [
                    'raw' => $entry,
                    'raw_unparsed' => true,
                    'parser' => 'apache_error',
                ],
            ];
        }

        [$module, $level] = $this->extractModuleAndLevel($matches['module_level'] ?? '');

        $message = trim($matches['message'] ?? $entry);

        [$file, $line] = $this->extractFileAndLine($message);

        return [
            'occurred_at' => $this->parseDate($matches['datetime']),
            'level' => $this->normalizeLevel($level),
            'message' => $message,
            'context' => [
                'parser' => 'apache_error',
                'module' => $module,
                'pid' => $matches['pid'] ?? null,
                'client' => $matches['client'] ?? null,
                'raw' => $entry,
            ],
            'ip_address' => $this->extractIp($matches['client'] ?? null),
            'method' => $this->extractRequestPart($message, 'method'),
            'url' => $this->extractRequestPart($message, 'url'),
            'status_code' => null,
            'exception_class' => null,
            'file' => $file,
            'line' => $line,
        ];
    }

    private function parseDate(string $date): Carbon
    {
        $formats = [
            'D M d H:i:s.u Y',
            'D M d H:i:s Y',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date);
            } catch (\Throwable) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return now();
        }
    }

    private function extractModuleAndLevel(string $moduleLevel): array
    {
        if (str_contains($moduleLevel, ':')) {
            [$module, $level] = explode(':', $moduleLevel, 2);

            return [$module, $level];
        }

        return [null, $moduleLevel ?: 'unknown'];
    }

    private
    function normalizeLevel(?string $level): string
    {
        $level = strtolower(trim((string)$level));

        return match ($level) {
            'emerg' => 'emergency',
            'crit' => 'critical',
            'warn' => 'warning',
            'notice' => 'notice',
            'info' => 'info',
            'debug' => 'debug',
            'error' => 'error',
            default => $level ?: 'unknown',
        };
    }

    private
    function extractIp(?string $client): ?string
    {
        if (!$client) {
            return null;
        }

        if (preg_match('/(?<ip>\d{1,3}(?:\.\d{1,3}){3})/', $client, $matches)) {
            return $matches['ip'];
        }

        if (preg_match('/(?<ip>[a-f0-9:]+):\d+$/i', $client, $matches)) {
            return $matches['ip'];
        }

        return $client;
    }

    private
    function extractFileAndLine(string $message): array
    {
        if (preg_match('/\sin\s(?<file>.+?)\son\sline\s(?<line>\d+)/i', $message, $matches)) {
            return [
                $matches['file'],
                (int)$matches['line'],
            ];
        }

        if (preg_match('/(?<file>\/[^\s:]+\.php):(?<line>\d+)/i', $message, $matches)) {
            return [
                $matches['file'],
                (int)$matches['line'],
            ];
        }

        return [null, null];
    }

    private
    function extractRequestPart(string $message, string $part): ?string
    {
        if (!preg_match('/request:\s+"(?<method>[A-Z]+)\s+(?<url>.*?)\s+HTTP\/[0-9.]+"?/i', $message, $matches)) {
            return null;
        }

        return $matches[$part] ?? null;
    }
}
