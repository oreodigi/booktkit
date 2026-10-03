<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureOrganizerAiSystemEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $isEnabled = (int) DB::table('basic_settings')
            ->where('uniqid', 12345)
            ->value('ai_system_status');

        if ($isEnabled !== 1) {
            abort(404);
        }

        return $next($request);
    }
}

