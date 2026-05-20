<?php

namespace Osit\LogSentinel\Services;

use Osit\LogSentinel\Contracts\LogParserInterface;
use Osit\LogSentinel\Models\LogSource;
use Osit\LogSentinel\Parsers\ApacheAccessLogParser;
use Osit\LogSentinel\Parsers\LaravelLogParser;
use Osit\LogSentinel\Parsers\NginxAccessLogParser;
use Osit\LogSentinel\Parsers\ApacheErrorLogParser;
use Osit\LogSentinel\Parsers\NginxErrorLogParser;
use Osit\LogSentinel\Parsers\LinuxSystemLogParser;
use Osit\LogSentinel\Parsers\SshAuthLogParser;
use Osit\LogSentinel\Parsers\MysqlErrorLogParser;
use Osit\LogSentinel\Parsers\PostgresqlLogParser;

class ParserResolver
{
    public function resolve(LogSource $source): ?LogParserInterface
    {
        $parser = $source->parser ?: $source->type;

        return match ($parser) {
            'laravel' => new LaravelLogParser(),
            'apache_access' => new ApacheAccessLogParser(),
            'nginx_access' => new NginxAccessLogParser(),
            'apache_error' => new ApacheErrorLogParser(),
            'nginx_error' => new NginxErrorLogParser(),
            'ssh_auth' => new SshAuthLogParser(),
            'linux_system' => new LinuxSystemLogParser(),
            'mysql_error' => new MysqlErrorLogParser(),
            'postgresql' => new PostgresqlLogParser(),
            default => null,
        };
    }
}
