<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Every method takes the owning User. There is deliberately no way to ask
 * for "a notification by id" without also saying whose it is — that is
 * what makes it impossible to read or modify someone else's inbox.
 */
interface NotificationRepositoryInterface
{
    public function paginateFor(User $user, int $perPage): LengthAwarePaginator;

    public function unreadCountFor(User $user): int;

    /**
     * @throws ModelNotFoundException when the
     *                                notification does not exist OR belongs to another user.
     */
    public function findFor(User $user, string $id): DatabaseNotification;

    public function markAllAsReadFor(User $user): int;
}
