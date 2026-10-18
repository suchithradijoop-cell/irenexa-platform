<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Support\AuditFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditTimelineService
{
    public function __construct(
        protected AuditLogRepositoryInterface $audit,
    ) {}

    public function list(AuditFilter $filter, int $perPage): LengthAwarePaginator
    {
        return $this->audit->paginate(max(1, min($perPage, 100)), $filter);
    }
}
