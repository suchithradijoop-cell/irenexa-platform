<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Repositories\Contracts\DealRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentDealRepository implements DealRepositoryInterface
{
    public function all(): Collection
    {
        return Deal::all();
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Deal::paginate($perPage);
    }

    public function find(int $id): ?Deal
    {
        return Deal::find($id);
    }

    public function create(array $data): Deal
    {
        return Deal::create($data);
    }

    public function totalAmountByStage(DealStage $stage): float
    {
        return (float) Deal::where('stage', $stage)->sum('amount');
    }
}
