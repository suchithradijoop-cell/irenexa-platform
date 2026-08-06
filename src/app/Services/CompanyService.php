<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
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

    public function createCompany(array $data): Company
    {
        return $this->companies->create($data);
    }
}
