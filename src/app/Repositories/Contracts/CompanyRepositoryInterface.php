<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Company;
use Illuminate\Support\Collection;

interface CompanyRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Company;

    public function create(array $data): Company;
}
