<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\HasActivities;
use App\Contracts\WorkflowAction;
use App\Enums\ActivityType;
use App\Services\ActivityService;
use InvalidArgumentException;

class CreateActivityAction implements WorkflowAction
{
    public function __construct(
        protected ActivityService $activityService,
    ) {}

    public function execute(array $config, array $context): void
    {
        $subject = $context['subject'] ?? null;

        if (! $subject instanceof HasActivities) {
            // Fails loudly and specifically, instead of silently doing
            // nothing — a misconfigured rule should be obvious to
            // whoever is debugging it, not a mystery.
            throw new InvalidArgumentException(
                'CreateActivityAction requires a "subject" in context implementing HasActivities.',
            );
        }

        $this->activityService->logActivity($subject, [
            'type' => $config['type'] ?? ActivityType::Note->value,
            'content' => $config['content'] ?? 'Automated activity',
        ]);
    }
}
