<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\Tenant;
use App\MultiTenancy\TenantContext;
use App\Repositories\MongoAuditLogRepository;
use App\Support\AuditFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration test against a REAL MongoDB. It is skipped unless
 * RUN_MONGO_TESTS=1, so the default suite (and CI without a Mongo service)
 * stays fast and self-contained. Run it locally with:
 *
 *   docker compose exec -e RUN_MONGO_TESTS=1 app php artisan test --filter=MongoAuditRepositoryTest
 */
class MongoAuditRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('RUN_MONGO_TESTS') !== '1') {
            $this->markTestSkipped('Set RUN_MONGO_TESTS=1 to run MongoDB integration tests.');
        }

        AuditEntry::withoutGlobalScopes()->where('event', 'like', 'itest.%')->delete();
    }

    protected function tearDown(): void
    {
        if (getenv('RUN_MONGO_TESTS') === '1') {
            AuditEntry::withoutGlobalScopes()->where('event', 'like', 'itest.%')->delete();
        }

        parent::tearDown();
    }

    public function test_entries_are_stamped_with_the_tenant_and_isolated_between_tenants(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $repository = new MongoAuditLogRepository;
        $context = app(TenantContext::class);

        $context->set($tenantA);
        $repository->record('itest.created', 'App\\Models\\Deal', 1, ['amount' => 10]);

        $context->set($tenantB);
        $repository->record('itest.created', 'App\\Models\\Deal', 2, ['amount' => 20]);

        // Tenant B sees only its own entry...
        $page = $repository->paginate(10, new AuditFilter(event: 'itest.created'));
        $this->assertSame(1, $page->total());
        $this->assertSame(2, $page->items()[0]->getAttribute('subject_id'));

        // ...and switching back shows only Tenant A's.
        $context->set($tenantA);
        $page = $repository->paginate(10, new AuditFilter(event: 'itest.created'));
        $this->assertSame(1, $page->total());
        $this->assertSame(1, $page->items()[0]->getAttribute('subject_id'));
    }
}
