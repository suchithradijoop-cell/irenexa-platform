<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\MultiTenancy\TenantContext;
use App\Services\WorkflowActionRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs one WorkflowRule's action on a background worker instead of inside
 * the original HTTP request (Lesson 12.1 — WorkflowEngine::fire() used to
 * run every matching rule's action synchronously; with enough rules, or one
 * slow action, that made the triggering request wait for all of them).
 *
 * IMPORTANT: the constructor deliberately takes a subject class + ID, not
 * the Eloquent model itself, and does NOT use SerializesModels' automatic
 * model-restoration for the subject. If an Eloquent model were a
 * constructor property here, Laravel would re-fetch it from the database
 * the moment this job is unserialized on the worker — BEFORE handle() has
 * a chance to run, which means before TenantContext is set. Because
 * TenantScope fails closed (Session Log, 2026-08-13), that re-fetch would
 * silently return nothing. Fetching the subject ourselves, inside
 * handle(), after we set TenantContext, avoids that entirely.
 */
class ExecuteWorkflowActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * $subjectType is the subject's Eloquent model class, e.g. App\Models\Deal.
     * $extraContext is everything from the WorkflowEngine context except
     * "subject" (e.g. ['deal_amount' => 12000.0]) — plain scalars only,
     * safe to serialize as-is.
     *
     * @param  class-string<Model>  $subjectType
     * @param  array<string, mixed>  $extraContext
     */
    public function __construct(
        protected int $tenantId,
        protected string $actionKey,
        protected array $actionConfig,
        protected string $subjectType,
        protected int $subjectId,
        protected array $extraContext,
    ) {}

    public function handle(TenantContext $tenantContext, WorkflowActionRegistry $actions): void
    {
        // Step 1: this worker process never went through ResolveTenant
        // middleware — there was no HTTP request at all. We establish the
        // tenant ourselves, explicitly, before touching anything
        // tenant-scoped.
        $tenant = Tenant::findOrFail($this->tenantId);
        $tenantContext->set($tenant);

        // Step 2: only safe to do this now — TenantScope will correctly
        // filter to $this->tenantId instead of failing closed.
        $subject = $this->subjectType::findOrFail($this->subjectId);

        $action = $actions->resolve($this->actionKey);

        $action->execute($this->actionConfig, [
            'subject' => $subject,
            ...$this->extraContext,
        ]);
    }
}
