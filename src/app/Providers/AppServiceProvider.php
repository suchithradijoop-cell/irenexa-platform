<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\MultiTenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
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
    }
}
