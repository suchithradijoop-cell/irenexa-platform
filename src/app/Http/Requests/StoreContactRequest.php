<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Contact::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:255'],

            // Plain 'exists:companies,id' would NOT be safe here — the
            // exists rule queries the table directly and does not know
            // about Eloquent's TenantScope global scope. Without the
            // ->where(...) below, a user could pass another tenant's
            // real company_id and it would pass validation. We add the
            // tenant condition ourselves so this check can't leak across
            // tenants.
            'company_id' => [
                'nullable',
                'integer',
                Rule::exists('companies', 'id')
                    ->where('tenant_id', $this->user()->tenant_id),
            ],
        ];
    }
}
