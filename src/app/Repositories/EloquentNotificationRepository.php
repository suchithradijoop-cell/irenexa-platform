<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function paginateFor(User $user, int $perPage): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate($perPage);
    }

    public function unreadCountFor(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->count();
    }

    public function findFor(User $user, string $id): DatabaseNotification
    {
        // Always queried THROUGH the user's own relation, so another
        // user's notification id simply is not found (404), exactly like
        // a record that does not exist.
        return $user->notifications()->whereKey($id)->firstOrFail();
    }

    public function markAllAsReadFor(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
