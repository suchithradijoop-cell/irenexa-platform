<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DatabaseNotification $notification */
        $notification = $this->resource;

        // The internal "type" column (a PHP class name) and the
        // notifiable_* columns are deliberately not exposed — they would
        // leak our namespace and storage details into the public API.
        // NOTE: the notification's payload is exposed as "content", never
        // "data". A resource whose own array has a top-level "data" key is
        // treated by Laravel as already wrapped, so the usual
        // {"data": {...}} envelope is silently skipped for single
        // resources (our own test caught this).
        return [
            'id' => $notification->id,
            'content' => $notification->data,
            'read' => $notification->read_at !== null,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }
}
