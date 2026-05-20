<?php

namespace Osit\LogSentinel\Parsers;

class SshAuthLogParser extends BaseSyslogParser
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
            'level' => $this->authLevel($message),
            'message' => $message,
            'context' => [
                'parser' => 'ssh_auth',
                'hostname' => $parsed['hostname'],
                'process' => $parsed['process'],
                'pid' => $parsed['pid'],
                'auth_event' => $this->authEvent($message),
                'user' => $this->extractUser($message),
                'port' => $this->extractPort($message),
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

    private function authLevel(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'failed password'),
            str_contains($lower, 'authentication failure'),
            str_contains($lower, 'invalid user'),
            str_contains($lower, 'user unknown'),
            str_contains($lower, 'maximum authentication attempts'),
            str_contains($lower, 'not allowed because') => 'warning',

            str_contains($lower, 'error'),
            str_contains($lower, 'fatal'),
            str_contains($lower, 'disconnecting') => 'error',

            str_contains($lower, 'accepted password'),
            str_contains($lower, 'accepted publickey'),
            str_contains($lower, 'session opened'),
            str_contains($lower, 'session closed') => 'info',

            default => $this->levelFromMessage($message),
        };
    }

    private function authEvent(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'failed password') => 'failed_password',
            str_contains($lower, 'accepted password') => 'accepted_password',
            str_contains($lower, 'accepted publickey') => 'accepted_publickey',
            str_contains($lower, 'invalid user') => 'invalid_user',
            str_contains($lower, 'authentication failure') => 'authentication_failure',
            str_contains($lower, 'session opened') => 'session_opened',
            str_contains($lower, 'session closed') => 'session_closed',
            str_contains($lower, 'sudo') => 'sudo_event',
            default => 'auth_event',
        };
    }
}
