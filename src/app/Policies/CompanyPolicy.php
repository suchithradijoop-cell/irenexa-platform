<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->tenant_id === $company->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->tenant_id === $company->tenant_id;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->tenant_id === $company->tenant_id
            && $user->role === UserRole::Admin;
    }
}
