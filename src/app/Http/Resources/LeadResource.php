<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // See ActivityResource for why this @var is needed — PHPStan
        // can't see through JsonResource's magic __get() forwarding.
        /** @var Lead $lead */
        $lead = $this->resource;

        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => $lead->status->value,

            // tenant_id is deliberately NOT here. A user can only ever
            // see their own tenant's leads anyway (TenantScope already
            // guarantees that), so this isn't hiding a security hole —
            // it's a design choice: the client never needed this
            // internal detail, and this Resource is where we decide
            // that on purpose, instead of it leaking out by accident.
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at,
        ];
    }
}
