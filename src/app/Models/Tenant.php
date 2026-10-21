<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class Tenant extends Model
{
    use HasFactory;

    /**
     * Note: Subscription carries the TenantScope, so this relation only
     * returns rows while the CURRENT tenant is this tenant (a tenant never
     * reads another tenant's subscriptions through here).
     *
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
