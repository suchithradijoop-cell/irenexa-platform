<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Support\SearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
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

    public function search(string $phrase, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $query = SearchQuery::fromUserInput($phrase);

        // Nothing searchable left after cleaning (e.g. "+ - *"): answer with
        // an empty page instead of asking the database a meaningless question.
        if ($query->isEmpty()) {
            return new Paginator([], 0, $perPage);
        }

        return $this->contacts->search($query, $perPage);
    }

    public function createContact(array $data): Contact
    {
        return $this->contacts->create($data);
    }
}
