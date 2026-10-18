<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contact;
use App\Support\SearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ContactRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage): LengthAwarePaginator;

    /**
     * Contacts of the current tenant matching the phrase, best match first.
     * The phrase must be non-empty; callers check SearchQuery::isEmpty().
     */
    public function search(SearchQuery $query, int $perPage): LengthAwarePaginator;

    public function find(int $id): ?Contact;

    public function create(array $data): Contact;
}
