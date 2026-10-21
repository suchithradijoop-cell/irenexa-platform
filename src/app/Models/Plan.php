<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A product definition: price and limits. Global (no BelongsToTenant): every
 * tenant sees the same catalogue. The tenant-specific part is Subscription.
 */
#[Fillable([
    'key', 'name', 'price_cents', 'currency', 'interval',
    'max_contacts', 'provider_price_id', 'is_active',
])]
class Plan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'max_contacts' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * NULL max_contacts means "no limit".
     */
    public function allowsContactCount(int $count): bool
    {
        $max = $this->getAttribute('max_contacts');

        return $max === null || $count <= (int) $max;
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
