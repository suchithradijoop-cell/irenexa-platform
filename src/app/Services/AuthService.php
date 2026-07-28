<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $users,
    ) {}

    public function register(array $data): User
    {
        return $this->users->create($data);
    }
}
