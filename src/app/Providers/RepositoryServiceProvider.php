<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\LeadRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
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
    }
}
