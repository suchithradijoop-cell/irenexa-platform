<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowRule;
use App\Services\WorkflowActionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_matching_workflow_rule_runs_its_action_on_lead_conversion(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        WorkflowRule::factory()->create([
            'tenant_id' => $tenant->id,
            'trigger' => 'lead.converted',
            'conditions' => null,
            'action' => 'create_activity',
            'action_config' => ['content' => 'Auto follow-up'],
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'company_name' => 'Acme Prospect',
            'deal_title' => 'First deal',
            'deal_amount' => 12000,
        ]);

        $response->assertCreated();
        // Lesson 10.2: DealResource responses are wrapped in a top-level
        // "data" key, so the deal's id is at "data.id", not "id".
        $dealId = $response->json('data.id');

        $this->assertDatabaseHas('activities', [
            'subject_type' => Deal::class,
            'subject_id' => $dealId,
            'content' => 'Auto follow-up',
        ]);
    }

    public function test_a_condition_blocks_the_action_when_it_does_not_match(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        WorkflowRule::factory()->create([
            'tenant_id' => $tenant->id,
            'trigger' => 'lead.converted',
            'conditions' => ['field' => 'deal_amount', 'operator' => '>', 'value' => 100000],
            'action' => 'create_activity',
            'action_config' => ['content' => 'Big deal follow-up'],
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        // deal_amount (12000) is below the rule's threshold (100000) —
        // conversion itself must still succeed; only the automation
        // should be skipped.
        $response = $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'company_name' => 'Small Prospect',
            'deal_title' => 'Small deal',
            'deal_amount' => 12000,
        ]);

        $response->assertCreated();

        $this->assertDatabaseMissing('activities', [
            'content' => 'Big deal follow-up',
        ]);
    }

    public function test_another_tenants_workflow_rule_never_fires(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userInTenantA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $leadInTenantA = Lead::factory()->create(['tenant_id' => $tenantA->id]);

        // Rule belongs to Tenant B — must never fire for Tenant A's
        // conversion, even though the trigger name matches exactly.
        WorkflowRule::factory()->create([
            'tenant_id' => $tenantB->id,
            'trigger' => 'lead.converted',
            'conditions' => null,
            'action' => 'create_activity',
            'action_config' => ['content' => 'Tenant B automation'],
            'is_active' => true,
        ]);

        Sanctum::actingAs($userInTenantA);

        $response = $this->postJson("/api/v1/leads/{$leadInTenantA->id}/convert", [
            'company_name' => 'Tenant A Prospect',
            'deal_title' => 'Tenant A deal',
            'deal_amount' => 5000,
        ]);

        $response->assertCreated();

        $this->assertDatabaseMissing('activities', [
            'content' => 'Tenant B automation',
        ]);
    }

    public function test_resolving_an_unknown_action_key_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(WorkflowActionRegistry::class)->resolve('does_not_exist');
    }
}
