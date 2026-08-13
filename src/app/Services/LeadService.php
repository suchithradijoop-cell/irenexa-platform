<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LeadService
{
    public function __construct(
        protected LeadRepositoryInterface $leads,
    ) {}

    public function listAll(): Collection
    {
        return $this->leads->all();
    }

    public function listPaginated(int $perPage): LengthAwarePaginator
    {
        // Clamped here, not trusted from the client — a request for
        // per_page=999999 would defeat the entire point of pagination
        // (loading everything at once, just via a different door).
        // min/max keeps it inside a sane, fixed range regardless of
        // what the client asks for.
        $perPage = max(1, min($perPage, 100));

        return $this->leads->paginate($perPage);
    }

    public function createLead(array $data): Lead
    {
        // Business rule: every new lead always starts as "New" status,
        // no matter who creates it or how (web form, API, import).
        $data['status'] = LeadStatus::New;

        return $this->leads->create($data);

        // NOTE: once Phase 6 (Authentication) and Phase 8 (CRM Core) exist,
        // this is exactly where rules like "assign to the rep with the
        // fewest active leads" and "notify the sales manager" will go —
        // same method, same guaranteed behavior for every entry point.
    }
}
