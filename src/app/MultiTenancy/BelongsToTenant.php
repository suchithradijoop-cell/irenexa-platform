<?php

declare(strict_types=1);

namespace App\MultiTenancy;

use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            if (! $model->getAttribute('tenant_id')) {
                $model->setAttribute('tenant_id', app(TenantContext::class)->id());
            }
        });
    }
}
