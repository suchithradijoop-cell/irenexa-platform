<?php

declare(strict_types=1);

namespace App\Providers;

use App\MultiTenancy\TenantContext;
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
        //
    }
}
