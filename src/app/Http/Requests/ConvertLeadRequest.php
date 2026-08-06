<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;

class ConvertLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Converting creates a Deal, so that's the permission that
        // actually matters here — a user who can't create Deals
        // shouldn't be able to produce one via a side door.
        return $this->user()->can('create', Deal::class);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'deal_title' => ['required', 'string', 'max:255'],
            'deal_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
