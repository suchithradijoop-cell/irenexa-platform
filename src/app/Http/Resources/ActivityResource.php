<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this->id, $this->type, etc. work fine at runtime — JsonResource
        // forwards unknown property access to the wrapped model via
        // __get(). PHPStan has no way to see through that magic method,
        // so it can't tell $this->id really means Activity::$id. This
        // @var tells it explicitly — same technique used in
        // EloquentActivityRepository (Lesson 8.8).
        /** @var Activity $activity */
        $activity = $this->resource;

        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'content' => $activity->content,
            'created_at' => $activity->created_at,
        ];
    }
}
