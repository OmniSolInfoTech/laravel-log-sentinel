<?php

namespace Osit\LogSentinel\Parsers;

class NginxAccessLogParser extends BaseAccessLogParser
{
    protected function parserName(): string
    {
        return 'nginx_access';
    }
}
