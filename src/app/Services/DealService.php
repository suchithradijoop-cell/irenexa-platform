<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Repositories\Contracts\DealRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DealService
{
    public function __construct(
        protected DealRepositoryInterface $deals,
    ) {}

    public function listAll(): Collection
    {
        return $this->deals->all();
    }

    public function listPaginated(int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return $this->deals->paginate($perPage);
    }

    public function createDeal(array $data): Deal
    {
        // Business rule, same shape as LeadService::createLead(): every
        // new deal always starts at the first pipeline stage, no matter
        // who creates it or how.
        $data['stage'] = DealStage::Prospecting;

        return $this->deals->create($data);
    }

    public function totalOpenPipelineValue(): float
    {
        return $this->deals->totalAmountByStage(DealStage::Prospecting)
            + $this->deals->totalAmountByStage(DealStage::Negotiation);
    }
}
