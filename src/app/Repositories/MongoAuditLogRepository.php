<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditEntry;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Support\AuditFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * MongoDB-backed audit store (Lesson 15.4). Same contract as the MySQL
 * implementation; only the storage differs. The tenant filter and the
 * tenant stamp come from AuditEntry's BelongsToTenant trait, so this class
 * never mentions tenants — and cannot forget to.
 */
class MongoAuditLogRepository implements AuditLogRepositoryInterface
{
    public function record(string $event, string $subjectType, int $subjectId, array $data = []): void
    {
        AuditEntry::create([
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'data' => $data,
        ]);
    }

    public function paginate(int $perPage, AuditFilter $filter = new AuditFilter): LengthAwarePaginator
    {
        return $filter->applyTo(AuditEntry::query())
            ->latest('created_at')
            ->paginate($perPage);
    }
}
