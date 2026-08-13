<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LeadRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage): LengthAwarePaginator;

    public function find(int $id): ?Lead;

    public function create(array $data): Lead;

    public function update(Lead $lead, array $data): Lead;
}
