<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Support\AuditFilter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class ListAuditRequest extends FormRequest
{
    /**
     * The audit trail shows who did what across the whole tenant, so only
     * tenant admins may read it.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // 'string' matters beyond tidiness: it rejects arrays, so a
            // request like ?event[$ne]=x — a classic NoSQL operator
            // injection attempt against MongoDB — fails validation before
            // it can ever reach a query.
            'event' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/i'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Dates are whole days in UTC: "from" starts at 00:00:00 and "to" ends
     * at 23:59:59, so a one-day range (from = to) returns that entire day.
     */
    public function toFilter(): AuditFilter
    {
        $from = $this->validated('from');
        $to = $this->validated('to');

        return new AuditFilter(
            event: $this->validated('event'),
            from: $from ? CarbonImmutable::parse($from, 'UTC')->startOfDay() : null,
            to: $to ? CarbonImmutable::parse($to, 'UTC')->endOfDay() : null,
        );
    }
}
