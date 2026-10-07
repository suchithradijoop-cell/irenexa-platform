<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationInboxService
{
    public function __construct(
        protected NotificationRepositoryInterface $notifications,
    ) {}

    public function list(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->notifications->paginateFor($user, max(1, min($perPage, 100)));
    }

    public function unreadCount(User $user): int
    {
        return $this->notifications->unreadCountFor($user);
    }

    public function markAsRead(User $user, string $id): DatabaseNotification
    {
        $notification = $this->notifications->findFor($user, $id);
        $notification->markAsRead();

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return $this->notifications->markAllAsReadFor($user);
    }
}
