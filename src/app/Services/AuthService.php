<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $users,
    ) {}

    public function register(array $data): User
    {
        $tenant = Tenant::where('slug', $data['tenant_slug'])->firstOrFail();

        unset($data['tenant_slug']);
        $data['tenant_id'] = $tenant->id;

        return $this->users->create($data);
    }

    public function login(array $credentials): string
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        return $user->createToken('api-token')->plainTextToken;
    }
}
