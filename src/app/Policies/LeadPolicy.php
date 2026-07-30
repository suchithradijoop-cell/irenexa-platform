<?php

declare(strict_types=1);

namespace App\Policies;

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
        return $user->tenant_id === $lead->tenant_id;
        // NOTE: once roles exist (Lesson 7.4), this will tighten to managers only.
    }
}
