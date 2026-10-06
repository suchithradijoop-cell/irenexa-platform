<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LeadConverted;
use App\Services\WorkflowEngine;

/**
 * Reacts to LeadConverted by running the tenant's "lead.converted"
 * workflow rules. Laravel auto-discovers this listener because handle()
 * type-hints the event class — no manual registration needed.
 */
class FireWorkflowRules
{
    public function __construct(
        protected WorkflowEngine $workflowEngine,
    ) {}

    public function handle(LeadConverted $event): void
    {
        $this->workflowEngine->fire('lead.converted', [
            'subject' => $event->deal,
            'deal_amount' => (float) $event->deal->amount,
        ]);
    }
}
