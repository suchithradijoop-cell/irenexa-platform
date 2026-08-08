<?php

declare(strict_types=1);

namespace App\Providers;

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

        $this->app->bind(
            ContactRepositoryInterface::class,
            EloquentContactRepository::class,
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
