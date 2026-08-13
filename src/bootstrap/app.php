<?php

use App\Http\Middleware\ResolveTenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // ADR 005: every route in routes/api.php now lives under
        // /api/v1/... instead of /api/... — a future breaking change
        // ships as a separate /api/v2 prefix, leaving this one
        // untouched for existing clients.
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 'api/*' still matches 'api/v1/*' — this is a wildcard prefix
        // check, not an exact match, so it did not need to change.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Eloquent's default message for a missing/route-model-binding
        // failure is "No query results for model [App\Models\Lead] 5" —
        // that leaks our internal PHP namespace straight into a public
        // API response. It also fires identically whether a record
        // truly doesn't exist OR it exists but belongs to another
        // tenant (TenantScope hides it) — a client can't tell the
        // difference, and for security reasons, shouldn't be able to.
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found.',
                ], 404);
            }
        });
    })->create();
