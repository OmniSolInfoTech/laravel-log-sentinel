<?php

namespace Osit\LogSentinel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLogSentinelEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('log-sentinel.enabled', true)) {
            abort(404);
        }

        return $next($request);
    }
}
