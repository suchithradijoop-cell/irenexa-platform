<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\EloquentAuditLogRepository;
use App\Repositories\MongoAuditLogRepository;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The only place that knows which database stores the audit stream is the
 * container binding. These tests pin its behaviour without needing a
 * running MongoDB: resolving a repository does not open a connection.
 */
class AuditStoreBindingTest extends TestCase
{
    public function test_mysql_is_the_store_when_configured(): void
    {
        config(['audit.store' => 'mysql']);

        $this->assertInstanceOf(
            EloquentAuditLogRepository::class,
            app(AuditLogRepositoryInterface::class),
        );
    }

    public function test_mongodb_is_the_store_when_configured(): void
    {
        config(['audit.store' => 'mongodb']);

        $this->assertInstanceOf(
            MongoAuditLogRepository::class,
            app(AuditLogRepositoryInterface::class),
        );
    }

    public function test_an_unknown_store_fails_loudly_instead_of_falling_back(): void
    {
        config(['audit.store' => 'mongo-db-typo']);

        $this->expectException(InvalidArgumentException::class);

        app(AuditLogRepositoryInterface::class);
    }
}
