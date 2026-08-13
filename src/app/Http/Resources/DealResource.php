<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // See ActivityResource for why this @var is needed.
        /** @var Deal $deal */
        $deal = $this->resource;

        return [
            'id' => $deal->id,
            'title' => $deal->title,
            'amount' => (float) $deal->amount,
            'stage' => $deal->stage->value,
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_at' => $deal->created_at,
            'updated_at' => $deal->updated_at,
        ];
    }
}
