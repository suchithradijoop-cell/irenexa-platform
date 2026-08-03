<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DealStage;
use App\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'amount', 'stage', 'contact_id', 'company_id'])]
class Deal extends Model
{
    use HasFactory, BelongsToTenant;

    protected function casts(): array
    {
        return [
            'stage' => DealStage::class,
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
