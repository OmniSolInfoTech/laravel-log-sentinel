<?php

namespace Osit\LogSentinel\Parsers;

use Carbon\Carbon;
use Osit\LogSentinel\Contracts\LogParserInterface;

abstract class BaseAccessLogParser implements LogParserInterface
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

        $pattern = '/^(?<ip>\S+)\s+\S+\s+\S+\s+\[(?<datetime>[^\]]+)\]\s+"(?<method>[A-Z]+)\s+(?<url>.*?)\s+(?<protocol>HTTP\/[0-9.]+)"\s+(?<status>\d{3})\s+(?<bytes>\S+)(?:\s+"(?<referer>[^"]*)"\s+"(?<user_agent>[^"]*)")?/';

        if (! preg_match($pattern, $entry, $matches)) {
            return [
                'occurred_at' => now(),
                'level' => 'unknown',
                'message' => $entry,
                'context' => [
                    'raw' => true,
                    'parser' => $this->parserName(),
                ],
            ];
        }

        $statusCode = (int) $matches['status'];

        return [
            'occurred_at' => $this->parseDate($matches['datetime']),
            'level' => $this->levelFromStatusCode($statusCode),
            'message' => $this->buildMessage($matches, $statusCode),
            'context' => [
                'parser' => $this->parserName(),
                'protocol' => $matches['protocol'] ?? null,
                'bytes' => ($matches['bytes'] ?? '-') === '-' ? null : (int) $matches['bytes'],
                'referer' => $matches['referer'] ?? null,
                'user_agent' => $matches['user_agent'] ?? null,
                'raw' => $entry,
            ],
            'ip_address' => $matches['ip'] ?? null,
            'method' => $matches['method'] ?? null,
            'url' => $matches['url'] ?? null,
            'status_code' => $statusCode,
            'exception_class' => null,
            'file' => null,
            'line' => null,
        ];
    }

    protected function parseDate(string $date): Carbon
    {
        try {
            return Carbon::createFromFormat('d/M/Y:H:i:s O', $date);
        } catch (\Throwable) {
            return now();
        }
    }

    protected function levelFromStatusCode(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            $statusCode >= 300 => 'notice',
            default => 'info',
        };
    }

    protected function buildMessage(array $matches, int $statusCode): string
    {
        $method = $matches['method'] ?? '-';
        $url = $matches['url'] ?? '-';

        return "{$method} {$url} returned HTTP {$statusCode}";
    }

    abstract protected function parserName(): string;
}
