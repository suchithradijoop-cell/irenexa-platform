<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ask the SAME question LeadPolicy::create() already knows how to
        // answer, instead of hardcoding an answer here. One rule, one home.
        // $this->user() works here because auth:sanctum has already run
        // (it's earlier in the route middleware group than this request).
        return $this->user()->can('create', Lead::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
