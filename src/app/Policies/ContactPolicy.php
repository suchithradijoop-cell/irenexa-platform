<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->tenant_id === $contact->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->tenant_id === $contact->tenant_id;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->tenant_id === $contact->tenant_id
            && $user->role === UserRole::Admin;
    }
}
