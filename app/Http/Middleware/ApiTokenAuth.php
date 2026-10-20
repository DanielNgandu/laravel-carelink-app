<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class ApiTokenAuth {
 public function handle(Request $request, Closure $next): Response {
    $token = $request->bearerToken();
    $found = null;
    if ($token) {
        foreach (config('carelink.tokens', []) as $candidate => $actor) {
            if (hash_equals((string) $candidate, $token)) { $found = $actor; break; }
        }
    }
    abort_unless($found !== null, 401, 'Valid bearer token required.');
    $request->attributes->set('actor', $found);
    return $next($request);
 }
}
