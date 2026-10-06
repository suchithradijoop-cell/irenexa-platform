<?php

declare(strict_types=1);

namespace App\MultiTenancy;

use App\Models\Tenant;

class TenantContext
{
    protected ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * For code running outside an HTTP request (queue jobs, queued
     * listeners) where no ResolveTenant middleware ran: establish the
     * tenant from a plain ID that was carried in the job/event payload.
     * Throws if the tenant no longer exists, so a stale job fails loudly
     * instead of running with no tenant.
     */
    public function setById(int $tenantId): void
    {
        $this->set(Tenant::findOrFail($tenantId));
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }
}
