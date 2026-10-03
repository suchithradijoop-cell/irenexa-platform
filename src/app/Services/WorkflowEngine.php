<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ExecuteWorkflowActionJob;
use App\Models\WorkflowRule;
use App\MultiTenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class WorkflowEngine
{
    public function __construct(
        protected WorkflowActionRegistry $actions,
        protected WorkflowConditionEvaluator $conditions,
        protected TenantContext $tenantContext,
    ) {}

    /**
     * @param  array  $context  Data about what just happened — e.g.
     *                          ['subject' => $contact, 'deal_amount' => 12000]
     */
    public function fire(string $trigger, array $context): void
    {
        // WorkflowRule uses BelongsToTenant (Lesson 9.2), so this query
        // is automatically scoped to the current tenant by TenantScope
        // (Phase 5) — we never add ->where('tenant_id', ...) ourselves.
        // It is structurally impossible for this engine to accidentally
        // run another tenant's rules.
        $rules = WorkflowRule::query()
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->get();

        $subject = $context['subject'] ?? null;

        if (! $subject instanceof Model) {
            throw new InvalidArgumentException(
                'WorkflowEngine::fire() context must include a "subject" Eloquent model.',
            );
        }

        $tenantId = $this->tenantContext->id();

        if ($tenantId === null) {
            // Fail loudly, not silently. Dispatching a job with an
            // unknown tenant would mean ExecuteWorkflowActionJob fails
            // trying to load that tenant anyway — better to catch this
            // here, with a clear message, than as a confusing failed job
            // in the queue later.
            throw new InvalidArgumentException(
                'WorkflowEngine::fire() called with no resolved tenant in context.',
            );
        }

        foreach ($rules as $rule) {
            if (! $this->conditions->passes($rule->conditions, $context)) {
                continue;
            }

            // Dispatched, not called directly (Lesson 12.3) — the action
            // runs later, on a worker, so this request doesn't wait for
            // it. We pass the subject's class + ID rather than the model
            // itself; see ExecuteWorkflowActionJob's docblock for why.
            ExecuteWorkflowActionJob::dispatch(
                $tenantId,
                $rule->action,
                $rule->action_config ?? [],
                $subject::class,
                (int) $subject->getKey(),
                Arr::except($context, ['subject']),
            );
        }
    }
}
