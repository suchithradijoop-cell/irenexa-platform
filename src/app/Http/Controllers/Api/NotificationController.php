<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use App\Services\NotificationInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationInboxService $inbox,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $this->inbox->list($this->user($request), $request->integer('per_page', 15)),
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ['unread' => $this->inbox->unreadCount($this->user($request))],
        ]);
    }

    public function markAsRead(Request $request, string $id): NotificationResource
    {
        return new NotificationResource(
            $this->inbox->markAsRead($this->user($request), $id),
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ['marked' => $this->inbox->markAllAsRead($this->user($request))],
        ]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
