<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityType;
use App\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['type', 'content'])]
class Activity extends Model
{
    use HasFactory, BelongsToTenant;

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        // No ::class argument here, unlike belongsTo() — morphTo() reads
        // the subject_type column at query time to figure out which
        // model to load. That's the whole trick: the relationship
        // decides its own target per-row, instead of always pointing
        // at one fixed model.
        return $this->morphTo();
    }
}
