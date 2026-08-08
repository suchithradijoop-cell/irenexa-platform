<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Contracts\HasActivities;
use App\Models\Activity;

interface ActivityRepositoryInterface
{
    public function createFor(HasActivities $subject, array $data): Activity;
}
