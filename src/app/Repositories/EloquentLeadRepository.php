<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Lead;
use App\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentLeadRepository implements LeadRepositoryInterface
{
    public function all(): Collection
    {
        return Lead::all();
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        // Eloquent's paginate() runs TWO queries: one COUNT(*) (to know
        // the total, for "last page" / "total" metadata) and one real
        // SELECT with LIMIT/OFFSET for just this page's rows. It never
        // loads more rows into memory than $perPage at once.
        return Lead::paginate($perPage);
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
