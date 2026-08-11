<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class WorkflowConditionEvaluator
{
    /**
     * $conditions shape: {"field": "deal_amount", "operator": ">", "value": 5000}
     * $context holds whatever data the trigger point supplied — we look
     * up $conditions['field'] inside it.
     */
    public function passes(?array $conditions, array $context): bool
    {
        // No condition configured on this rule means "always run" —
        // matches WorkflowRule migration's comment (Lesson 9.2).
        if ($conditions === null) {
            return true;
        }

        $actual = $context[$conditions['field']] ?? null;
        $expected = $conditions['value'];

        return match ($conditions['operator']) {
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            default => throw new InvalidArgumentException(
                "Unknown workflow condition operator: {$conditions['operator']}",
            ),
        };
    }
}
