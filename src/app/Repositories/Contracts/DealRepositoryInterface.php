<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\DealStage;
use App\Models\Deal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DealRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage): LengthAwarePaginator;

    public function find(int $id): ?Deal;

    public function create(array $data): Deal;

    // The reason this interface exists separately from a generic
    // repository: this question ("how much money is sitting in a given
    // stage right now?") is a real, recurring business need — a sales
    // dashboard would call this directly — and it belongs here, not
    // hand-written inline wherever someone happens to need it.
    public function totalAmountByStage(DealStage $stage): float;
}
