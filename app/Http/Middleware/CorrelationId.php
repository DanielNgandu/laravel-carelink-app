<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
class CorrelationId {
 public function handle(Request $request, Closure $next): Response {
    // A fresh server-generated ID avoids header-injection and untrusted values in logs.
    $id = (string) Str::uuid();
    $request->attributes->set('correlation_id', $id);
    $start = microtime(true);
    try { $response = $next($request); $response->headers->set('X-Correlation-Id', $id); return $response; }
    finally { Log::info('api.request', ['correlation_id' => $id, 'method' => $request->method(),
         'route' => $request->route()?->uri(), 'duration_ms' => round((microtime(true) - $start) * 1000)]); }
 }
}
