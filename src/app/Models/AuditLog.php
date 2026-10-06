<?php

declare(strict_types=1);

namespace App\Models;

use App\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only record of something that happened (Lesson 13.4). Written
 * once by listeners, never edited — hence no updated_at.
 */
#[Fillable(['event', 'subject_type', 'subject_id', 'data'])]
class AuditLog extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
