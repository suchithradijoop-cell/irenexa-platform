<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The subject (Contact/Company/Deal) is already tenant-checked by
        // route model binding (Lesson 8.7's lesson applies here too) — if
        // it resolved at all, it's already this user's tenant. So the
        // only remaining question is "can this user log activities at
        // all," which every authenticated user can.
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(ActivityType::class)],
            'content' => ['required', 'string', 'max:2000'],
        ];
    }
}
