<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_resource_hides_internal_fields(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Lead::factory()->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/leads');

        $response->assertOk();

        $lead = $response->json('data.0');

        // Public fields must be present...
        $this->assertArrayHasKey('id', $lead);
        $this->assertArrayHasKey('name', $lead);
        $this->assertArrayHasKey('email', $lead);
        $this->assertArrayHasKey('status', $lead);

        // ...but tenant_id — an internal detail — must never appear in
        // the response, proving LeadResource (Lesson 10.2) is actually
        // controlling the JSON shape, not just passing the model through.
        $this->assertArrayNotHasKey('tenant_id', $lead);
    }

    public function test_index_endpoints_return_pagination_metadata(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Lead::factory()->count(3)->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/leads');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
        $this->assertSame(3, $response->json('meta.total'));
    }

    public function test_unversioned_api_path_no_longer_exists(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        // The old, unversioned path from before Lesson 10.4 — proving the
        // apiPrefix change actually took effect, not just that /api/v1
        // happens to also work.
        $response = $this->getJson('/api/leads');

        $response->assertNotFound();
    }

    public function test_accessing_another_tenants_lead_returns_generic_not_found(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userInTenantA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $leadInTenantB = Lead::factory()->create(['tenant_id' => $tenantB->id]);

        Sanctum::actingAs($userInTenantA);

        $response = $this->postJson("/api/v1/leads/{$leadInTenantB->id}/convert", [
            'company_name' => 'Should Not Matter',
            'deal_title' => 'Should Not Matter',
            'deal_amount' => 100,
        ]);

        $response->assertNotFound();
        $response->assertJson(['message' => 'Resource not found.']);

        // The real point of this test: prove the leaky Eloquent message
        // is gone — no PHP namespace, no model class name, anywhere in
        // the response body.
        $response->assertDontSee('App\\Models\\Lead', false);
        $response->assertDontSee('No query results', false);
    }
}
