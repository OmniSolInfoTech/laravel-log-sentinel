<?php

namespace Osit\LogSentinel\Services;

class Redactor
{
    public function redactMixed(mixed $value): mixed
    {
        if (! config('log-sentinel.redaction.enabled', true)) {
            return $value;
        }

        if (is_string($value)) {
            return $this->redactString($value);
        }

        if (is_array($value)) {
            return $this->redactArray($value);
        }

        return $value;
    }

    public function redactArray(array $data): array
    {
        $redacted = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $redacted[$key] = $this->mask();
                continue;
            }

            $redacted[$key] = $this->redactMixed($value);
        }

        return $redacted;
    }

    public function redactString(string $value): string
    {
        $patterns = [
            '/(authorization:\s*bearer\s+)[a-z0-9\._\-]+/i' => '$1' . $this->mask(),
            '/(bearer\s+)[a-z0-9\._\-]+/i' => '$1' . $this->mask(),

            '/((?:password|passwd|pwd)\s*[=:]\s*)[^&\s,"\'}]+/i' => '$1' . $this->mask(),
            '/((?:token|api_key|apikey|secret|csrf)\s*[=:]\s*)[^&\s,"\'}]+/i' => '$1' . $this->mask(),

            '/("?(?:password|passwd|pwd|token|api_key|apikey|secret|csrf)"?\s*:\s*")[^"]+(")/i' => '$1' . $this->mask() . '$2',

            '/((?:session|cookie)\s*[=:]\s*)[^&\s,"\'}]+/i' => '$1' . $this->mask(),
        ];

        foreach ($patterns as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value);
        }

        if (config('log-sentinel.redaction.mask_emails', false)) {
            $value = preg_replace(
                '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
                $this->mask(),
                $value
            );
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        foreach (config('log-sentinel.redaction.sensitive_keys', []) as $sensitiveKey) {
            if (str_contains($key, strtolower($sensitiveKey))) {
                return true;
            }
        }

        return false;
    }

    private function mask(): string
    {
        return config('log-sentinel.redaction.mask', '[REDACTED]');
    }
}
