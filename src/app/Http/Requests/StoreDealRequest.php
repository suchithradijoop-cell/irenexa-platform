<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Deal::class);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],

            // Same tenant-scoped exists() as StoreContactRequest — both
            // must belong to this user's own tenant, not just exist
            // anywhere in the table.
            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where('tenant_id', $tenantId),
            ],
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where('tenant_id', $tenantId),
            ],
        ];
    }
}
