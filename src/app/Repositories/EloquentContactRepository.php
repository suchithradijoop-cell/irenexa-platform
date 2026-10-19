<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentContactRepository implements ContactRepositoryInterface
{
    public function all(): Collection
    {
        return Contact::all();
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Contact::paginate($perPage);
    }

    public function find(int $id): ?Contact
    {
        return Contact::find($id);
    }

    public function create(array $data): Contact
    {
        return Contact::create($data);
    }
}
