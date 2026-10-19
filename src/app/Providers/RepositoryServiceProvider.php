<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\CachedContactRepository;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\DealRepositoryInterface;
use App\Repositories\Contracts\LeadRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\EloquentActivityRepository;
use App\Repositories\EloquentAuditLogRepository;
use App\Repositories\EloquentCompanyRepository;
use App\Repositories\EloquentContactRepository;
use App\Repositories\EloquentDealRepository;
use App\Repositories\EloquentLeadRepository;
use App\Repositories\EloquentNotificationRepository;
use App\Repositories\EloquentUserRepository;
use App\Repositories\MongoAuditLogRepository;
use App\Search\ContactSearcher;
use App\Search\MeilisearchContactSearcher;
use App\Search\MysqlContactSearcher;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

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

        // The one place that knows which engine answers contact searches
        // (ADR 010). Unknown values throw, like AUDIT_STORE.
        $this->app->bind(
            ContactSearcher::class,
            fn ($app) => match (config('search.driver')) {
                'mysql' => $app->make(MysqlContactSearcher::class),
                'meilisearch' => $app->make(MeilisearchContactSearcher::class),
                default => throw new InvalidArgumentException(
                    'Unknown search driver ['.config('search.driver').']. Use "mysql" or "meilisearch".',
                ),
            },
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

        $this->app->bind(
            NotificationRepositoryInterface::class,
            EloquentNotificationRepository::class,
        );

        // The one place that knows which audit store is active (ADR 009).
        // An unknown value throws instead of silently falling back, so a
        // typo in AUDIT_STORE is caught immediately rather than quietly
        // writing audit data to the wrong database.
        $this->app->bind(
            AuditLogRepositoryInterface::class,
            fn ($app) => match (config('audit.store')) {
                'mysql' => $app->make(EloquentAuditLogRepository::class),
                'mongodb' => $app->make(MongoAuditLogRepository::class),
                default => throw new InvalidArgumentException(
                    'Unknown audit store ['.config('audit.store').']. Use "mysql" or "mongodb".',
                ),
            },
        );
    }
}
