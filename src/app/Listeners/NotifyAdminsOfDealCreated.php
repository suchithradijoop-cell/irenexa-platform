<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\LeadConverted;
use App\Models\Deal;
use App\Models\User;
use App\MultiTenancy\TenantContext;
use App\Notifications\DealCreatedNotification;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Third reaction to LeadConverted (Lesson 14.2): tell the tenant's admins.
 * Like WriteAuditLog, it was added without touching the service or the
 * other listeners.
 */
class NotifyAdminsOfDealCreated implements ShouldHandleEventsAfterCommit, ShouldQueue
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
        $this->tenantContext->setById($event->tenantId);

        $deal = Deal::findOrFail($event->dealId);

        // User has no TenantScope (authentication must be able to find a
        // user before any tenant is known), so the tenant filter is
        // explicit here. Forgetting it would notify admins of EVERY
        // tenant — the one line in this class that must never be removed.
        $admins = User::query()
            ->where('tenant_id', $event->tenantId)
            ->where('role', UserRole::Admin)
            ->get();

        Notification::send($admins, new DealCreatedNotification(
            (int) $deal->id,
            (string) $deal->title,
            (float) $deal->amount,
        ));
    }
}
