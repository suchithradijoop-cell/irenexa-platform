<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LeadConverted;
use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\Tenant;
use App\MultiTenancy\TenantContext;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * A SECOND reaction to LeadConverted (Lesson 13.4). Adding it required no
 * change to LeadConversionService or to FireWorkflowRules — only a new
 * file. That is the Open/Closed Principle, demonstrated.
 */
class WriteAuditLog implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function __construct(
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
        $this->tenantContext->set(Tenant::findOrFail($event->tenantId));

        $deal = Deal::findOrFail($event->dealId);

        // tenant_id is stamped automatically by BelongsToTenant from the
        // TenantContext we just set.
        AuditLog::create([
            'event' => 'lead.converted',
            'subject_type' => Deal::class,
            'subject_id' => $deal->id,
            'data' => ['deal_amount' => (float) $deal->amount],
        ]);
    }
}
