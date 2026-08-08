<?php

declare(strict_types=1);

namespace App\Models;

use App\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['trigger', 'conditions', 'action', 'action_config', 'is_active'])]
class WorkflowRule extends Model
{
    use HasFactory, BelongsToTenant;

    protected function casts(): array
    {
        return [
            // Laravel automatically JSON-encodes/decodes these, so in
            // PHP they're always plain arrays — never raw JSON strings
            // to parse by hand.
            'conditions' => 'array',
            'action_config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
