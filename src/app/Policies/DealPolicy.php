<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deal $deal): bool
    {
        return $user->tenant_id === $deal->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Deal $deal): bool
    {
        return $user->tenant_id === $deal->tenant_id;
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->tenant_id === $deal->tenant_id
            && $user->role === UserRole::Admin;
    }
}
