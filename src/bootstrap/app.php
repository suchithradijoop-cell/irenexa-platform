<?php

use App\Http\Middleware\ResolveTenant;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        // CRITICAL ORDERING FIX. Laravel's SubstituteBindings middleware
        // (which turns a route's {lead}/{contact}/{company}/{deal} into
        // a real, loaded model) is a GLOBAL middleware, and by default
        // it is not guaranteed to run after a custom alias middleware
        // like 'tenant' — our own tests caught it running BEFORE
        // ResolveTenant had set TenantContext, meaning TenantScope saw
        // no tenant yet at the exact moment a route parameter's model
        // was being looked up. This explicit priority list forces:
        // authenticate the user -> resolve their tenant -> THEN resolve
        // route-bound models. Every tenant-scoped route parameter is now
        // guaranteed to be looked up only after we know which tenant is
        // making the request.
        $middleware->priority([
            Authenticate::class,
            ResolveTenant::class,
            SubstituteBindings::class,
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
        //
        // NOTE: this is intentionally typed as NotFoundHttpException,
        // not ModelNotFoundException. Laravel's own exception handler
        // converts ModelNotFoundException into NotFoundHttpException
        // (reusing its leaky message) BEFORE checking any custom
        // render() callback — a ModelNotFoundException type-hint here
        // would never actually match anything, which our own tests
        // caught. Catching every 404 on api/* and returning one generic
        // message is simpler and equally safe either way.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found.',
                ], 404);
            }
        });
    })->create();
