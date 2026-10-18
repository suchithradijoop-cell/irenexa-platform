<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * MySQL-backed audit store (the original implementation, Lesson 13.4).
 */
class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function record(string $event, string $subjectType, int $subjectId, array $data = []): void
    {
        AuditLog::create([
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'data' => $data,
        ]);
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return AuditLog::query()->latest('created_at')->paginate($perPage);
    }
}
