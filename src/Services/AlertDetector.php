<?php

namespace Osit\LogSentinel\Services;

use Illuminate\Support\Str;
use Osit\LogSentinel\Models\LogEntry;
use Osit\LogSentinel\Models\SecurityAlert;

class AlertDetector
{
    public function detect(LogEntry $entry): void
    {
        foreach ($this->rules($entry) as $alert) {
            $this->createOrUpdateAlert($entry, $alert);
        }
    }

    private function rules(LogEntry $entry): array
    {
        $alerts = [];

        $message = strtolower((string) $entry->message);
        $url = strtolower((string) $entry->url);
        $sourceType = strtolower((string) $entry->source_type);
        $level = strtolower((string) $entry->level);
        $context = $entry->context ?? [];

        /*
        |--------------------------------------------------------------------------
        | Web security probes
        |--------------------------------------------------------------------------
        */

        $sensitivePaths = [
            '.env',
            '.git',
            'wp-admin',
            'wp-login',
            'phpmyadmin',
            'adminer',
            'backup',
            '.sql',
            '.bak',
            'config.php',
        ];

        foreach ($sensitivePaths as $path) {
            if (str_contains($url, $path) || str_contains($message, $path)) {
                $alerts[] = [
                    'severity' => 'high',
                    'type' => 'sensitive_path_probe',
                    'title' => 'Sensitive path access attempt',
                    'description' => "A request or log entry referenced a sensitive path: {$path}.",
                    'key' => $path,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | HTTP status alerts
        |--------------------------------------------------------------------------
        */

        if ($entry->status_code >= 500) {
            $alerts[] = [
                'severity' => 'high',
                'type' => 'http_server_error',
                'title' => 'HTTP server error detected',
                'description' => "A {$entry->status_code} server error was detected.",
                'key' => (string) $entry->status_code,
            ];
        }

        if ((int) $entry->status_code === 403) {
            $alerts[] = [
                'severity' => 'medium',
                'type' => 'http_forbidden',
                'title' => 'Forbidden request detected',
                'description' => 'A 403 forbidden response was detected.',
                'key' => '403',
            ];
        }

        if ((int) $entry->status_code === 401) {
            $alerts[] = [
                'severity' => 'medium',
                'type' => 'http_unauthorized',
                'title' => 'Unauthorized request detected',
                'description' => 'A 401 unauthorized response was detected.',
                'key' => '401',
            ];
        }

        if ((int) $entry->status_code === 404 && $entry->ip_address) {
            $alerts[] = [
                'severity' => 'low',
                'type' => 'http_not_found',
                'title' => '404 not found request detected',
                'description' => 'A 404 not found response was detected.',
                'key' => '404',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | SSH/Auth alerts
        |--------------------------------------------------------------------------
        */

        $authEvent = $context['auth_event'] ?? null;

        if ($sourceType === 'ssh_auth') {
            if (in_array($authEvent, ['failed_password', 'invalid_user', 'authentication_failure'], true)) {
                $alerts[] = [
                    'severity' => 'high',
                    'type' => 'ssh_failed_login',
                    'title' => 'Failed SSH login attempt',
                    'description' => 'A failed or invalid SSH login attempt was detected.',
                    'key' => (string) ($authEvent ?: 'ssh_failed_login'),
                ];
            }

            if ($authEvent === 'accepted_password' || $authEvent === 'accepted_publickey') {
                $alerts[] = [
                    'severity' => 'low',
                    'type' => 'ssh_successful_login',
                    'title' => 'Successful SSH login',
                    'description' => 'A successful SSH login was detected.',
                    'key' => (string) $authEvent,
                ];
            }

            if ($authEvent === 'sudo_event') {
                $alerts[] = [
                    'severity' => 'medium',
                    'type' => 'sudo_event',
                    'title' => 'Sudo activity detected',
                    'description' => 'A sudo-related authentication event was detected.',
                    'key' => 'sudo',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Database alerts
        |--------------------------------------------------------------------------
        */

        $databaseEvent = $context['database_event'] ?? null;

        if (in_array($databaseEvent, ['access_denied', 'auth_failed'], true)) {
            $alerts[] = [
                'severity' => 'high',
                'type' => 'database_auth_failure',
                'title' => 'Database authentication failure',
                'description' => 'A database authentication failure was detected.',
                'key' => (string) $databaseEvent,
            ];
        }

        if (in_array($databaseEvent, ['deadlock', 'too_many_connections'], true)) {
            $alerts[] = [
                'severity' => 'medium',
                'type' => 'database_runtime_issue',
                'title' => 'Database runtime issue detected',
                'description' => "A database runtime issue was detected: {$databaseEvent}.",
                'key' => (string) $databaseEvent,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | System alerts
        |--------------------------------------------------------------------------
        */

        if (str_contains($message, 'out of memory') || ($context['event_type'] ?? null) === 'memory') {
            $alerts[] = [
                'severity' => 'critical',
                'type' => 'system_out_of_memory',
                'title' => 'Out of memory event detected',
                'description' => 'The system reported an out-of-memory event.',
                'key' => 'oom',
            ];
        }

        if (str_contains($message, 'failed to start')) {
            $alerts[] = [
                'severity' => 'high',
                'type' => 'service_failure',
                'title' => 'Service failure detected',
                'description' => 'A system service failed to start.',
                'key' => 'service_failed',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Laravel / application errors
        |--------------------------------------------------------------------------
        */

        if (in_array($level, ['critical', 'alert', 'emergency'], true)) {
            $alerts[] = [
                'severity' => 'critical',
                'type' => 'application_critical',
                'title' => 'Critical application error',
                'description' => 'A critical application-level log entry was detected.',
                'key' => $level,
            ];
        }

        if ($sourceType === 'laravel' && $level === 'error') {
            $alerts[] = [
                'severity' => 'medium',
                'type' => 'laravel_error',
                'title' => 'Laravel error detected',
                'description' => 'A Laravel error log entry was detected.',
                'key' => $entry->exception_class ?: 'laravel_error',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Apache/Nginx permission issues
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($message, 'permission denied') ||
            str_contains($message, 'client denied') ||
            str_contains($message, 'access forbidden')
        ) {
            $alerts[] = [
                'severity' => 'high',
                'type' => 'web_permission_denied',
                'title' => 'Web server permission denied',
                'description' => 'Apache or Nginx reported a permission/access denied event.',
                'key' => 'permission_denied',
            ];
        }

        return $alerts;
    }

    private function createOrUpdateAlert(LogEntry $entry, array $alert): void
    {
        $fingerprint = $this->fingerprint($entry, $alert);

        $existingAlert = SecurityAlert::query()
            ->where('fingerprint', $fingerprint)
            ->whereIn('status', ['open', 'acknowledged'])
            ->first();

        if ($existingAlert) {
            $existingAlert->update([
                'entry_id' => $entry->id,
                'last_seen_at' => $entry->occurred_at ?? now(),
                'occurrence_count' => $existingAlert->occurrence_count + 1,
                'description' => $alert['description'],
                'meta' => array_merge($existingAlert->meta ?? [], [
                    'latest_log_entry_id' => $entry->id,
                    'latest_message' => Str::limit($entry->message, 500),
                ]),
            ]);

            return;
        }

        SecurityAlert::create([
            'entry_id' => $entry->id,
            'source_id' => $entry->source_id,
            'severity' => $alert['severity'],
            'type' => $alert['type'],
            'title' => $alert['title'],
            'description' => $alert['description'],
            'ip_address' => $entry->ip_address,
            'fingerprint' => $fingerprint,
            'occurrence_count' => 1,
            'first_seen_at' => $entry->occurred_at ?? now(),
            'last_seen_at' => $entry->occurred_at ?? now(),
            'status' => 'open',
            'meta' => [
                'rule_key' => $alert['key'] ?? null,
                'source_type' => $entry->source_type,
                'log_entry_id' => $entry->id,
                'message' => Str::limit($entry->message, 500),
                'url' => $entry->url,
                'status_code' => $entry->status_code,
            ],
        ]);
    }

    private function fingerprint(LogEntry $entry, array $alert): string
    {
        return sha1(implode('|', [
            $alert['type'],
            $entry->source_type,
            $entry->ip_address ?: 'no-ip',
            $alert['key'] ?? 'no-key',
        ]));
    }
}
