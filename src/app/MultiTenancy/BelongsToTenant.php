<?php

declare(strict_types=1);

namespace App\MultiTenancy;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
