<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Every workflow action (CreateActivityAction, and future actions like
 * SendNotificationAction) must implement this one method. The
 * WorkflowEngine (Lesson 9.4) only ever calls execute() — it never needs
 * to know which concrete action it's actually running. That's the whole
 * point of the Strategy pattern: interchangeable behavior behind one
 * shared contract.
 */
interface WorkflowAction
{
    /**
     * @param array $config  Per-rule settings from WorkflowRule::$action_config
     *                       (e.g. ['content' => 'Follow up with prospect'])
     * @param array $context Data about what triggered this (e.g. ['subject' => $contact])
     */
    public function execute(array $config, array $context): void;
}
