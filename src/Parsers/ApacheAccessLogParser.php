<?php

namespace Osit\LogSentinel\Parsers;

class ApacheAccessLogParser extends BaseAccessLogParser
{
    protected function parserName(): string
    {
        return 'apache_access';
    }
}
