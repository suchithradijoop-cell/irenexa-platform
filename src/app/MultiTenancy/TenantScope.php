<?php

declare(strict_types=1);

namespace App\MultiTenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        // FAIL CLOSED, not open. Before this fix, a null $tenantId meant
        // "add no filter at all" — silently returning every tenant's
        // rows. That is exactly backwards for a security boundary: if we
        // don't yet know which tenant this request belongs to (e.g.
        // route model binding running before ResolveTenant middleware
        // has set the context — a real bug this project's own tests
        // caught), the safe default is to show NOTHING, not everything.
        //
        // -1 can never match a real tenant_id (auto-increment starts at
        // 1), so this reliably returns zero rows instead of needing a
        // separate raw-SQL "always false" trick.
        $builder->where('tenant_id', $tenantId ?? -1);
    }
}
