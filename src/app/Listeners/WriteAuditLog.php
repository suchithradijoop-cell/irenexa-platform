<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LeadConverted;
use App\Models\Deal;
use App\MultiTenancy\TenantContext;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
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
        protected AuditLogRepositoryInterface $auditLog,
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
        $this->tenantContext->setById($event->tenantId);

        $deal = Deal::findOrFail($event->dealId);

        // Which database stores this is decided by config (ADR 009), not
        // here. tenant_id is stamped by the model's BelongsToTenant trait
        // from the TenantContext we just set — in either store.
        $this->auditLog->record(
            'lead.converted',
            Deal::class,
            (int) $deal->id,
            ['deal_amount' => (float) $deal->amount],
        );
    }
}
