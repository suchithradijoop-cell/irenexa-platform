<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\MultiTenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One TenantContext per request, shared by everything that asks
        // for it — not a fresh one built every time (see Lesson 5.2).
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Illustrative example (Lesson 7.2) — a simple, standalone gate not
        // tied to any one model. Will be replaced by a real role check once
        // roles exist (Lesson 7.4).
        Gate::define('view-all-tenants', function (User $user) {
            return str_ends_with($user->email, '@irenexa.com');
        });

        // Brute-force protection for /register and /login. Keyed by IP,
        // not by user — at this point in the request, no user is
        // authenticated yet, so IP is the only identity we have. 5 tries
        // per minute is enough for a genuine typo, not enough to
        // meaningfully guess a password.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // General API traffic, applied to every tenant-scoped route.
        // Keyed by the authenticated user's ID (these routes always run
        // after auth:sanctum, so a user is guaranteed to exist) rather
        // than IP, so one user's usage never counts against another user
        // sharing the same IP (e.g. an office network).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
