<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_cannot_delete_a_lead(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::Member,
        ]);
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertFalse($member->can('delete', $lead));
    }

    public function test_an_admin_can_delete_a_lead_in_their_own_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::Admin,
        ]);
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertTrue($admin->can('delete', $lead));
    }

    public function test_an_admin_cannot_delete_a_lead_in_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminInTenantA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => UserRole::Admin,
        ]);
        $leadInTenantB = Lead::factory()->create(['tenant_id' => $tenantB->id]);

        // Being an admin is not enough on its own — being an admin of the
        // WRONG company still isn't allowed. Both checks in
        // LeadPolicy::delete() must pass, not just one.
        $this->assertFalse($adminInTenantA->can('delete', $leadInTenantB));
    }
}
