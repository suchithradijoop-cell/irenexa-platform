<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
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

    public function createContact(array $data): Contact
    {
        return $this->contacts->create($data);
    }
}
