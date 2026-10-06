<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ExecuteWorkflowActionJob;
use App\Listeners\FireWorkflowRules;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowRule;
use App\MultiTenancy\TenantContext;
use App\Services\WorkflowActionRegistry;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QueuedWorkflowJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_conversion_dispatches_the_job_instead_of_running_the_action_inline(): void
    {
        // Queue::fake() swaps the real queue for a recorder: jobs are
        // captured, never executed.
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        WorkflowRule::factory()->create([
            'tenant_id' => $tenant->id,
            'trigger' => 'lead.converted',
            'conditions' => null,
            'action' => 'create_activity',
            'action_config' => ['content' => 'Queued follow-up'],
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'company_name' => 'Acme',
            'deal_title' => 'Deal',
            'deal_amount' => 1000,
        ])->assertCreated();

        // Since Lesson 13.3 there are two queue hops: the request only
        // queues the FireWorkflowRules listener (Laravel wraps queued
        // listeners in CallQueuedListener). The per-rule
        // ExecuteWorkflowActionJob is dispatched later, by that listener,
        // once a worker runs it — so it must NOT exist yet.
        Queue::assertPushed(
            CallQueuedListener::class,
            fn (CallQueuedListener $job) => $job->class === FireWorkflowRules::class,
        );
        Queue::assertNotPushed(ExecuteWorkflowActionJob::class);

        // Proof the request did not wait for the automation.
        $this->assertDatabaseMissing('activities', ['content' => 'Queued follow-up']);
    }

    public function test_the_job_sets_its_own_tenant_context_on_the_worker(): void
    {
        $tenant = Tenant::factory()->create();
        $contact = Contact::factory()->create(['tenant_id' => $tenant->id]);

        // No HTTP request happened, so no middleware has set a tenant —
        // exactly the situation on a real queue worker.
        $context = app(TenantContext::class);
        $this->assertNull($context->id());

        $job = new ExecuteWorkflowActionJob(
            $tenant->id,
            'create_activity',
            ['content' => 'From the worker'],
            Contact::class,
            $contact->id,
            [],
        );

        $job->handle($context, app(WorkflowActionRegistry::class));

        $this->assertSame($tenant->id, $context->id());
        $this->assertDatabaseHas('activities', [
            'tenant_id' => $tenant->id,
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'content' => 'From the worker',
        ]);
    }

    public function test_the_job_cannot_load_another_tenants_subject(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $contactOfB = Contact::factory()->create(['tenant_id' => $tenantB->id]);

        // Tenant A's job pointed at Tenant B's record: TenantScope must
        // hide it, so the lookup fails instead of leaking data.
        $job = new ExecuteWorkflowActionJob(
            $tenantA->id,
            'create_activity',
            [],
            Contact::class,
            $contactOfB->id,
            [],
        );

        $this->expectException(ModelNotFoundException::class);

        $job->handle(app(TenantContext::class), app(WorkflowActionRegistry::class));
    }

    public function test_an_unknown_action_key_fails_permanently_without_side_effects(): void
    {
        $tenant = Tenant::factory()->create();
        $contact = Contact::factory()->create(['tenant_id' => $tenant->id]);

        $job = new ExecuteWorkflowActionJob(
            $tenant->id,
            'does_not_exist',
            [],
            Contact::class,
            $contact->id,
            [],
        );

        // fail() is called internally; handle() returns quietly rather
        // than throwing, so the queue will not retry it.
        $job->handle(app(TenantContext::class), app(WorkflowActionRegistry::class));

        $this->assertDatabaseCount('activities', 0);
    }
}
