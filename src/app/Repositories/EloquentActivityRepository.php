<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\HasActivities;
use App\Models\Activity;
use App\Repositories\Contracts\ActivityRepositoryInterface;

class EloquentActivityRepository implements ActivityRepositoryInterface
{
    public function createFor(HasActivities $subject, array $data): Activity
    {
        // $subject->activities() is the morphMany relationship — calling
        // create() through it automatically fills in subject_type and
        // subject_id for us, matching whichever model was passed in.
        // We never have to write those two columns by hand.
        //
        // HasActivities::activities() is deliberately non-generic (see
        // that interface's own comment), so PHPStan only knows this
        // returns "some Model," not specifically an Activity. We know it
        // always IS an Activity — every implementer's activities() is
        // wired to Activity::class — so we say so explicitly here, in
        // the one place that actually needs the precise type.
        /** @var Activity $activity */
        $activity = $subject->activities()->create($data);

        return $activity;
    }
}
