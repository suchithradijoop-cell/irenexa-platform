<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The filters a caller may apply to the audit timeline, as one immutable
 * value. Repositories receive this object instead of loose arguments, so
 * adding a filter later changes one class, not every method signature.
 *
 * applyTo() uses only the Eloquent query API that MySQL and MongoDB models
 * share (where / where with operators), so both stores filter identically
 * from a single implementation.
 */
final class AuditFilter
{
    public function __construct(
        public readonly ?string $event = null,
        public readonly ?CarbonInterface $from = null,
        public readonly ?CarbonInterface $to = null,
    ) {}

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function applyTo(Builder $query): Builder
    {
        if ($this->event !== null) {
            $query->where('event', $this->event);
        }

        if ($this->from !== null) {
            $query->where('created_at', '>=', $this->from);
        }

        if ($this->to !== null) {
            $query->where('created_at', '<=', $this->to);
        }

        return $query;
    }
}
