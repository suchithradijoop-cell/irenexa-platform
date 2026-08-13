<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ContactService
{
    public function __construct(
        protected ContactRepositoryInterface $contacts,
    ) {}

    public function listAll(): Collection
    {
        return $this->contacts->all();
    }

    public function listPaginated(int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return $this->contacts->paginate($perPage);
    }

    public function createContact(array $data): Contact
    {
        return $this->contacts->create($data);
    }
}
