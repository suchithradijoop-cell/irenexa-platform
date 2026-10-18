<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Runs against the MySQL-compatible store (phpunit.xml pins
 * AUDIT_STORE=mysql). Because the endpoint talks only to
 * AuditLogRepositoryInterface, these tests describe the API contract for
 * whichever store is active.
 */
class AuditTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function user(Tenant $tenant, UserRole $role): User
    {
        return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
    }

    private function entry(Tenant $tenant, string $event, string $createdAt, array $data = []): void
    {
        // forceCreate: tenant_id is not mass-assignable on purpose, and a
        // fixed created_at is needed to test date filters.
        AuditLog::forceCreate([
            'tenant_id' => $tenant->id,
            'event' => $event,
            'subject_type' => Deal::class,
            'subject_id' => 1,
            'data' => $data,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function tenantWithAdminAndEntries(): array
    {
        $tenant = Tenant::factory()->create();
        $admin = $this->user($tenant, UserRole::Admin);

        $this->entry($tenant, 'lead.converted', '2026-09-01 10:00:00', ['deal_amount' => 100.0]);
        $this->entry($tenant, 'lead.converted', '2026-10-05 10:00:00', ['deal_amount' => 200.0]);
        $this->entry($tenant, 'other.event', '2026-10-05 12:00:00');

        return [$tenant, $admin];
    }

    public function test_an_admin_sees_only_their_own_tenants_entries_newest_first(): void
    {
        [, $admin] = $this->tenantWithAdminAndEntries();

        // Another tenant's entry must never appear.
        $otherTenant = Tenant::factory()->create();
        $this->entry($otherTenant, 'lead.converted', '2026-10-06 09:00:00');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/audit')->assertOk();

        $this->assertSame(3, $response->json('meta.total'));
        $this->assertSame('other.event', $response->json('data.0.event'));
    }

    public function test_entries_expose_a_stable_public_shape(): void
    {
        [, $admin] = $this->tenantWithAdminAndEntries();
        Sanctum::actingAs($admin);

        $entry = $this->getJson('/api/v1/audit?event=lead.converted')
            ->assertOk()
            ->json('data.0');

        $this->assertArrayNotHasKey('tenant_id', $entry);
        $this->assertSame('Deal', $entry['subject']['type']);
        $this->assertSame(200.0, (float) $entry['details']['deal_amount']);
        $this->assertIsString($entry['id']);
    }

    public function test_it_filters_by_event_and_by_date_range(): void
    {
        [, $admin] = $this->tenantWithAdminAndEntries();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/audit?event=lead.converted')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/v1/audit?from=2026-10-01')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/v1/audit?to=2026-09-30')
            ->assertJsonPath('meta.total', 1);

        // from = to returns the WHOLE day, including entries late in it.
        $this->getJson('/api/v1/audit?from=2026-10-05&to=2026-10-05')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_a_member_cannot_read_the_audit_trail(): void
    {
        $tenant = Tenant::factory()->create();
        Sanctum::actingAs($this->user($tenant, UserRole::Member));

        $this->getJson('/api/v1/audit')->assertForbidden();
    }

    public function test_a_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/audit')->assertUnauthorized();
    }

    public function test_operator_style_filters_are_rejected_before_reaching_a_query(): void
    {
        [, $admin] = $this->tenantWithAdminAndEntries();
        Sanctum::actingAs($admin);

        // The classic NoSQL injection shape: PHP parses it into an array,
        // and the 'string' rule must refuse it. Single quotes keep PHP from
        // treating $ne as a variable.
        $this->getJson('/api/v1/audit?event[$ne]=x')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event');
    }

    public function test_invalid_ranges_and_page_sizes_are_rejected(): void
    {
        [, $admin] = $this->tenantWithAdminAndEntries();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/audit?from=2026-10-05&to=2026-10-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');

        $this->getJson('/api/v1/audit?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }
}
