<?php

use App\Http\Middleware\ApiTokenAuth;
use App\Http\Middleware\CorrelationId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Http\Middleware\AssignSessionActor;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [CorrelationId::class]);
        $middleware->alias(['clinic.token' => ApiTokenAuth::class,'clinic.session' => AssignSessionActor::class,
]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) return null;
            $status = $e instanceof ValidationException ? 400 : ($e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500);
            $code = match ($status) {
                400 => 'VALIDATION_FAILED', 401 => 'UNAUTHENTICATED', 403 => 'FORBIDDEN',
                404 => 'NOT_FOUND', 409 => 'CONFLICT', 429 => 'RATE_LIMITED', default => 'INTERNAL_ERROR',
            };
            $message = $e instanceof ValidationException
                ? implode(' ', $e->validator->errors()->all())
                : ($status === 500 ? 'An unexpected error occurred.' : ($e->getMessage() ?: 'Request failed.'));
            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];
            return response()->json(['error' => ['code' => $code, 'message' => $message,
                'correlation_id' => $request->attributes->get('correlation_id')]], $status, $headers);
        });
    })->create();
