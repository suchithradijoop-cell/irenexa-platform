<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One tenant on one plan. tenant_id is NOT fillable (same rule as every
 * tenant-owned model): ownership is stamped by BelongsToTenant, or set by
 * trusted code with forceFill when a webhook has identified the tenant.
 */
#[Fillable([
    'plan_id', 'status', 'provider', 'provider_customer_id',
    'provider_subscription_id', 'current_period_ends_at', 'canceled_at',
])]
class Subscription extends Model
{
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'current_period_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function grantsAccess(): bool
    {
        /** @var SubscriptionStatus $status */
        $status = $this->getAttribute('status');

        return $status->grantsAccess();
    }
}
