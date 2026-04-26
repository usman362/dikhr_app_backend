<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // Subscription-only model: lets through trial users AND
            // paid users. This is the gate applied to every data API
            // route — see api_v1.php.
            'active_access' => \App\Http\Middleware\EnsureActiveAccess::class,
        ]);

        // Global stack — runs on EVERY response, web + api. Adds
        // security headers + strips framework fingerprints (X-Powered-By
        // etc.) so the server doesn't advertise what it's built with.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Trust the X-Forwarded-* headers from the load balancer /
        // reverse proxy in front of us — without this, $request->secure()
        // is false even when the user came in over HTTPS, and the HSTS
        // header above never gets emitted in production.
        $middleware->trustProxies(at: '*');

        // API middleware stack
        $middleware->api(prepend: [
            \App\Http\Middleware\SanitizeInput::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ── Unified JSON error responses for the mobile app ─────────
        //
        // Without these handlers, Laravel responds with HTML error
        // pages on certain exceptions (404 for unknown routes, 500 on
        // uncaught errors, etc.) — which crashes the mobile Dio JSON
        // decoder. Every API request gets a JSON envelope back so the
        // mobile error handler always has a `message` field to show.
        //
        // Format:
        //   { "message": "Human-readable error", "code": "validation" }
        //
        // Stack traces are included only when APP_DEBUG=true (dev).

        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }
            return response()->json([
                'message' => $e->getMessage(),
                'code'    => 'validation',
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }
            return response()->json([
                'message' => 'Authentication required.',
                'code'    => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }
            // Hide the model class name from the client — we don't want
            // "App\Models\Group" leaking to the mobile UI. Just say
            // the resource wasn't found.
            return response()->json([
                'message' => 'Resource not found.',
                'code'    => 'not_found',
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }
            return response()->json([
                'message' => 'Endpoint not found.',
                'code'    => 'route_not_found',
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }
            // `abort(403, '...')` and friends end up here. Preserve the
            // status code and the message the developer passed in.
            $msg = $e->getMessage() ?: match ($e->getStatusCode()) {
                403 => 'Forbidden.',
                404 => 'Not found.',
                429 => 'Too many requests. Please slow down.',
                default => 'Request failed.',
            };
            return response()->json([
                'message' => $msg,
                'code'    => 'http_'.$e->getStatusCode(),
            ], $e->getStatusCode());
        });

        // Generic catch-all — last so the more specific handlers win.
        // In production this hides the actual exception message from
        // the user (security: no SQL strings, no file paths leaking).
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }
            $debug = config('app.debug');
            $payload = [
                'message' => $debug ? $e->getMessage() : 'Something went wrong. Please try again.',
                'code'    => 'server_error',
            ];
            // Only ship a (truncated) stack trace in dev — never in
            // production, where it would leak file paths and code.
            if ($debug) {
                $payload['trace'] = collect($e->getTrace())->take(5)->toArray();
            }
            return response()->json($payload, 500);
        });
    })->create();
