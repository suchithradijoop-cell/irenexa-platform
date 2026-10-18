<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One public JSON shape for an audit entry, whichever database stored it
 * (an integer id from MySQL, an ObjectId string from MongoDB). The
 * tenant_id and the internal PHP class name are not exposed.
 */
class AuditEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Model $entry */
        $entry = $this->resource;

        // The payload is exposed as "details", never "data": a resource
        // with its own top-level "data" key loses Laravel's envelope
        // (the lesson from NotificationResource).
        return [
            'id' => (string) $entry->getKey(),
            'event' => $entry->getAttribute('event'),
            'subject' => [
                'type' => class_basename((string) $entry->getAttribute('subject_type')),
                'id' => $entry->getAttribute('subject_id'),
            ],
            'details' => $entry->getAttribute('data') ?? [],
            'created_at' => $entry->getAttribute('created_at'),
        ];
    }
}
