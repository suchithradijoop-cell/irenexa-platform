<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // See ActivityResource for why this @var is needed.
        /** @var Contact $contact */
        $contact = $this->resource;

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'job_title' => $contact->job_title,

            // whenLoaded() only includes "company" in the JSON if the
            // controller actually eager-loaded it (Company::with('contacts')
            // style, or here, Contact::with('company')). If it wasn't
            // loaded, this key is left out of the response entirely —
            // instead of silently triggering a NEW database query per
            // Contact (the classic "N+1 query" mistake).
            'company' => new CompanyResource($this->whenLoaded('company')),

            'created_at' => $contact->created_at,
            'updated_at' => $contact->updated_at,
        ];
    }
}
