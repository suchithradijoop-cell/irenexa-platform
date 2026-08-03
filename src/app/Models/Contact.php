<?php

declare(strict_types=1);

namespace App\Models;

use App\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// company_id IS in this Fillable list — unlike tenant_id, it's not a
// security boundary. Which company a contact works for is ordinary
// business data a user is allowed to set. tenant_id stays out because
// that's WHO OWNS the data, a much more dangerous thing to let a client
// control.
#[Fillable(['name', 'email', 'phone', 'job_title', 'company_id'])]
class Contact extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
