<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Announces a fact: a Lead has been converted into a Deal. Named in the
 * past tense on purpose — it reports something that already happened, it
 * does not ask anyone to do anything.
 *
 * Carries plain IDs, not the Deal model (Lesson 13.3). Listeners may run
 * on a queue worker, where a model property would be re-fetched before the
 * listener has set TenantContext — the same trap as ExecuteWorkflowActionJob
 * (Lesson 12.3). IDs are scalars: safe to serialize, and each listener
 * loads what it needs after establishing the tenant.
 */
class LeadConverted
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $dealId,
    ) {}
}
