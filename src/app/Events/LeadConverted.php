<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Announces a fact: a Lead has been converted into a Deal. Named in the
 * past tense on purpose — it reports something that already happened, it
 * does not ask anyone to do anything. Who reacts (workflow rules, audit
 * log, notifications) is decided by the registered listeners, not here.
 */
class LeadConverted
{
    use Dispatchable;

    public function __construct(
        public readonly Deal $deal,
    ) {}
}
