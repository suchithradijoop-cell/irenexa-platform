<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Support\AuditFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The audit stream, independent of where it is stored. Today there are two
 * implementations (MySQL and MongoDB); callers depend only on this
 * interface and never know which one is active (ADR 009).
 *
 * Both methods work on the CURRENT tenant, resolved from TenantContext by
 * the model's global scope / stamping, exactly like every other
 * tenant-scoped model in the project.
 */
interface AuditLogRepositoryInterface
{
    /**
     * Append one entry. Entries are never updated or deleted.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(string $event, string $subjectType, int $subjectId, array $data = []): void;

    /**
     * Newest-first page of the current tenant's entries, optionally
     * narrowed by the filter.
     */
    public function paginate(int $perPage, AuditFilter $filter = new AuditFilter): LengthAwarePaginator;
}
