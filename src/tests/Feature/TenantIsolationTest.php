<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_cannot_see_another_tenants_leads(): void
    {
        $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
        $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

        Lead::withoutGlobalScopes()->forceCreate([
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Lead',
            'email' => 'a@example.com',
        ]);

        Lead::withoutGlobalScopes()->forceCreate([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Lead',
            'email' => 'b@example.com',
        ]);

        $response = $this->withHeader('X-Tenant', 'tenant-a')
            ->getJson('/api/leads');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Tenant A Lead']);
        $response->assertJsonMissing(['name' => 'Tenant B Lead']);
    }

    public function test_creating_a_lead_automatically_stamps_the_current_tenant(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'tenant-c']);

        $response = $this->withHeader('X-Tenant', 'tenant-c')
            ->postJson('/api/leads', [
                'name' => 'New Lead',
                'email' => 'new@example.com',
                'tenant_id' => 999, // attempted spoof — must be ignored
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('leads', [
            'name' => 'New Lead',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseMissing('leads', [
            'name' => 'New Lead',
            'tenant_id' => 999,
        ]);
    }
}
