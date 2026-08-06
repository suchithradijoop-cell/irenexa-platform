<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Lead;
use App\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentLeadRepository implements LeadRepositoryInterface
{
    public function all(): Collection
    {
        return Lead::all();
    }

    public function find(int $id): ?Lead
    {
        return Lead::find($id);
    }

    public function create(array $data): Lead
    {
        return Lead::create($data);
    }

    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);

        return $lead;
    }
}
