<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\LeadConverted;
use App\Listeners\FireWorkflowRules;
use App\Listeners\WriteAuditLog;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadConvertedEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Tenant, 1: User, 2: Lead}
     */
    private function tenantWithUserAndLead(): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        return [$tenant, $user, $lead];
    }

    public function test_converting_a_lead_announces_the_event_with_plain_ids(): void
    {
        // Event::fake() records dispatches and stops listeners from
        // running — this test is only about "was the fact announced?"
        Event::fake([LeadConverted::class]);

        [$tenant, $user, $lead] = $this->tenantWithUserAndLead();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'company_name' => 'Acme',
            'deal_title' => 'Deal',
            'deal_amount' => 2500,
        ])->assertCreated();

        $dealId = $response->json('data.id');

        Event::assertDispatched(
            LeadConverted::class,
            fn (LeadConverted $event) => $event->tenantId === $tenant->id
                && $event->dealId === $dealId,
        );
    }

    public function test_both_reactions_are_registered_for_the_event(): void
    {
        // Guards against a listener silently losing its registration
        // (e.g. a renamed handle() type-hint breaks auto-discovery).
        // assertListening() belongs to the fake, so the fake must be set
        // up first; it keeps the app's real listener registrations and
        // only inspects them.
        Event::fake([LeadConverted::class]);

        Event::assertListening(LeadConverted::class, FireWorkflowRules::class);
        Event::assertListening(LeadConverted::class, WriteAuditLog::class);
    }

    public function test_conversion_writes_a_tenant_scoped_audit_record(): void
    {
        // No fake: the real event, the real queued listeners (run inline
        // by the sync queue driver in tests).
        [$tenant, $user, $lead] = $this->tenantWithUserAndLead();
        Sanctum::actingAs($user);

        $dealId = $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'company_name' => 'Acme',
            'deal_title' => 'Deal',
            'deal_amount' => 2500,
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'lead.converted',
            'subject_type' => Deal::class,
            'subject_id' => $dealId,
        ]);
    }

    public function test_another_tenant_gets_no_audit_record_from_this_conversion(): void
    {
        $otherTenant = Tenant::factory()->create();

        [, $user, $lead] = $this->tenantWithUserAndLead();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'company_name' => 'Acme',
            'deal_title' => 'Deal',
            'deal_amount' => 2500,
        ])->assertCreated();

        $this->assertDatabaseMissing('audit_logs', [
            'tenant_id' => $otherTenant->id,
        ]);
    }
}
