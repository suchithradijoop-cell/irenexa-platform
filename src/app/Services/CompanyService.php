<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CompanyService
{
    public function __construct(
        protected CompanyRepositoryInterface $companies,
    ) {}

    public function listAll(): Collection
    {
        return $this->companies->all();
    }

    public function listPaginated(int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return $this->companies->paginate($perPage);
    }

    public function createCompany(array $data): Company
    {
        return $this->companies->create($data);
    }
}
