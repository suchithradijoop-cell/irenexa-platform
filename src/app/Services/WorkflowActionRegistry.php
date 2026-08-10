<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\CreateActivityAction;
use App\Contracts\WorkflowAction;
use InvalidArgumentException;

class WorkflowActionRegistry
{
    /**
     * This array is the ONLY place a WorkflowRule's action key ever gets
     * turned into real code. Nothing from the database is ever passed
     * directly to a class-instantiation call — only keys that already
     * exist here, hardcoded by us, can ever run. A tenant admin can pick
     * from this fixed list; they can never invent a new one.
     *
     * @var array<string, class-string<WorkflowAction>>
     */
    protected array $actions = [
        'create_activity' => CreateActivityAction::class,
    ];

    public function resolve(string $key): WorkflowAction
    {
        if (! isset($this->actions[$key])) {
            throw new InvalidArgumentException("Unknown workflow action: {$key}");
        }

        // app() asks the Service Container (Phase 2) to build the class
        // — so CreateActivityAction's own constructor dependency
        // (ActivityService) is auto-injected here too, exactly the same
        // as when Laravel builds a Controller.
        return app($this->actions[$key]);
    }
}
