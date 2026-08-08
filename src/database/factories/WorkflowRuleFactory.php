<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WorkflowRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowRule>
 */
class WorkflowRuleFactory extends Factory
{
    protected $model = WorkflowRule::class;

    public function definition(): array
    {
        return [
            'trigger' => 'lead.converted',
            'conditions' => null,
            'action' => 'create_activity',
            'action_config' => ['content' => 'Follow up with new deal'],
            'is_active' => true,
        ];
    }
}
