<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // any authenticated user can list leads
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function create(User $user): bool
    {
        return true; // any authenticated user can create a lead
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        // Deleting is more dangerous than viewing or updating, so it now
        // needs BOTH: same company (tenant match) AND admin role.
        // A regular member can no longer delete leads at all.
        return $user->tenant_id === $lead->tenant_id
            && $user->role === UserRole::Admin;
    }
}
