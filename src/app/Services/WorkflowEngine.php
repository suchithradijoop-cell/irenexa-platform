<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WorkflowRule;

class WorkflowEngine
{
    public function __construct(
        protected WorkflowActionRegistry $actions,
        protected WorkflowConditionEvaluator $conditions,
    ) {}

    /**
     * @param array $context Data about what just happened — e.g.
     *                       ['subject' => $contact, 'deal_amount' => 12000]
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

        foreach ($rules as $rule) {
            if (! $this->conditions->passes($rule->conditions, $context)) {
                continue;
            }

            $action = $this->actions->resolve($rule->action);
            $action->execute($rule->action_config ?? [], $context);
        }
    }
}
