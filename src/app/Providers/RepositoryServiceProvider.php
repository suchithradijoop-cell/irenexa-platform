<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\CachedContactRepository;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\DealRepositoryInterface;
use App\Repositories\Contracts\LeadRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\EloquentActivityRepository;
use App\Repositories\EloquentCompanyRepository;
use App\Repositories\EloquentContactRepository;
use App\Repositories\EloquentDealRepository;
use App\Repositories\EloquentLeadRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            LeadRepositoryInterface::class,
            EloquentLeadRepository::class,
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class,
        );

        // Bound to the caching decorator, not EloquentContactRepository
        // directly — every consumer of ContactRepositoryInterface (the
        // ContactService, and anything else built against the interface)
        // automatically gets caching with zero code changes on their side.
        // That's the point of depending on an interface instead of a
        // concrete class (Lesson 4.2).
        $this->app->bind(
            ContactRepositoryInterface::class,
            CachedContactRepository::class,
        );

        $this->app->bind(
            CompanyRepositoryInterface::class,
            EloquentCompanyRepository::class,
        );

        $this->app->bind(
            DealRepositoryInterface::class,
            EloquentDealRepository::class,
        );

        $this->app->bind(
            ActivityRepositoryInterface::class,
            EloquentActivityRepository::class,
        );
    }
}
