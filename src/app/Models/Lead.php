<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadStatus;
use App\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'status'])]
class Lead extends Model
{
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
        ];
    }
}
