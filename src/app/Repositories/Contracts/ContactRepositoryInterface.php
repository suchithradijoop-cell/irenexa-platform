<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ContactRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage): LengthAwarePaginator;

    public function find(int $id): ?Contact;

    public function create(array $data): Contact;
}
