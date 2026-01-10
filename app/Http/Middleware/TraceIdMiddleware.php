<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class TraceIdMiddleware
{
    public function handle($request, Closure $next)
    {
        $traceId = $request->header('X-Trace-Id') ?: (string) Str::uuid();

        $request->attributes->set('trace_id', $traceId);

        Log::withContext([
            'trace_id' => $traceId,
            'path'     => $request->path(),
            'method'   => $request->method(),
            'user_id'  => optional($request->user())->id,
        ]);

        $response = $next($request);
        $response->headers->set('X-Trace-Id', $traceId);

        return $response;
    }
}
