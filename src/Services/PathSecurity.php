<?php

namespace Osit\LogSentinel\Services;

class PathSecurity
{
    public function isAllowed(string $path): bool
    {
        $path = trim($path);

        if ($path === '') {
            return false;
        }

        if (config('log-sentinel.path_security.block_path_traversal', true)) {
            if (str_contains($path, '..')) {
                return false;
            }
        }

        if (config('log-sentinel.path_security.block_stream_wrappers', true)) {
            if ($this->hasStreamWrapper($path)) {
                return false;
            }
        }

        if (! config('log-sentinel.path_security.allow_only_configured_paths', true)) {
            return true;
        }

        $normalizedPath = $this->normalizePath($path);

        foreach (config('log-sentinel.path_security.allowed_base_paths', []) as $basePath) {
            $normalizedBasePath = $this->normalizePath($basePath);

            if ($normalizedBasePath === '') {
                continue;
            }

            if ($this->pathStartsWith($normalizedPath, $normalizedBasePath)) {
                return true;
            }
        }

        return false;
    }

    public function reason(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return 'Path cannot be empty.';
        }

        if (config('log-sentinel.path_security.block_path_traversal', true) && str_contains($path, '..')) {
            return 'Path traversal is not allowed.';
        }

        if (config('log-sentinel.path_security.block_stream_wrappers', true) && $this->hasStreamWrapper($path)) {
            return 'Stream wrappers are not allowed.';
        }

        if (! $this->isAllowed($path)) {
            return 'This path is not allowed by the Log Sentinel path security settings.';
        }

        return null;
    }

    private function hasStreamWrapper(string $path): bool
    {
        return (bool) preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//', $path);
    }

    private function normalizePath(string $path): string
    {
        $realPath = realpath($path);

        if ($realPath !== false) {
            $path = $realPath;
        }

        return rtrim(str_replace('\\', '/', $path), '/');
    }

    private function pathStartsWith(string $path, string $basePath): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $basePath = strtolower($basePath);
        }

        return $path === $basePath || str_starts_with($path, $basePath . '/');
    }
}
