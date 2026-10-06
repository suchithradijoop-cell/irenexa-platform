<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LeadConverted;
use App\Models\Deal;
use App\Models\Tenant;
use App\MultiTenancy\TenantContext;
use App\Services\WorkflowEngine;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Reacts to LeadConverted by running the tenant's "lead.converted"
 * workflow rules — on a queue worker, not inside the HTTP request.
 *
 * ShouldQueue: the listener itself becomes a queued job.
 * ShouldHandleEventsAfterCommit: if the event is ever dispatched inside a
 * database transaction, the listener waits until that transaction has
 * committed, so a worker can never look for a Deal that is not yet saved
 * (or that gets rolled back).
 */
class FireWorkflowRules implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        protected WorkflowEngine $workflowEngine,
        protected TenantContext $tenantContext,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(LeadConverted $event): void
    {
        // A queue worker has no ResolveTenant middleware: establish the
        // tenant first, then load the Deal through TenantScope.
        $this->tenantContext->set(Tenant::findOrFail($event->tenantId));

        $deal = Deal::findOrFail($event->dealId);

        $this->workflowEngine->fire('lead.converted', [
            'subject' => $deal,
            'deal_amount' => (float) $deal->amount,
        ]);
    }
}
