<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

        // Tenant is no longer taken from a header — it comes from the
        // logged-in user (Lesson 6.6). So the test must log a real
        // user in, belonging to tenant A, instead of sending a header.
        $userInTenantA = User::factory()->create(['tenant_id' => $tenantA->id]);
        Sanctum::actingAs($userInTenantA);

        $response = $this->getJson('/api/v1/leads');

        $response->assertOk();
        // Lesson 10.2: LeadResource::collection() wraps output in a
        // top-level "data" key, so we now count items inside "data",
        // not at the JSON root.
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'Tenant A Lead']);
        $response->assertJsonMissing(['name' => 'Tenant B Lead']);
    }

    public function test_creating_a_lead_automatically_stamps_the_current_tenant(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'tenant-c']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/leads', [
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

    public function test_a_guest_cannot_see_any_leads(): void
    {
        // No Sanctum::actingAs() — nobody is logged in.
        $response = $this->getJson('/api/v1/leads');

        $response->assertUnauthorized(); // 401
    }
}
