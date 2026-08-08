<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\HasActivities;
use App\Models\Activity;
use App\Repositories\Contracts\ActivityRepositoryInterface;

class ActivityService
{
    public function __construct(
        protected ActivityRepositoryInterface $activities,
    ) {}

    public function logActivity(HasActivities $subject, array $data): Activity
    {
        return $this->activities->createFor($subject, $data);
    }
}
