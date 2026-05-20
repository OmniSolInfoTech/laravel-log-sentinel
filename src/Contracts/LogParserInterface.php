<?php

namespace Osit\LogSentinel\Contracts;

interface LogParserInterface
{
    public function splitEntries(string $contents): array;

    public function parse(string $entry): ?array;
}
