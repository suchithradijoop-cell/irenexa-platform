<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchContactsRequest extends FormRequest
{
    /**
     * Any authenticated user of the tenant may search their own tenant's
     * contacts (same rule as ContactPolicy::viewAny). Tenant isolation is
     * enforced by TenantScope, not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // 'string' rejects ?q[]=x style arrays; the cap keeps queries cheap.
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
