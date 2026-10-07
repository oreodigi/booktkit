<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Guards legacy HTTP cron endpoints. Disabled (404) unless config('booktkit.cron_http_token')
 * is set, and then requires a matching ?token= or X-Cron-Token header.
 */
class EnsureCronToken
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('booktkit.cron_http_token', '');
        $given = (string) ($request->header('X-Cron-Token') ?: $request->query('token', ''));
        if ($expected === '' || $given === '' || !hash_equals($expected, $given)) {
            abort(404);
        }
        return $next($request);
    }
}
