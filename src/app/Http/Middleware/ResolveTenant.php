<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\MultiTenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-Tenant');

        if (! $slug) {
            abort(400, 'Missing X-Tenant header.');
        }

        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        $this->tenantContext->set($tenant);

        return $next($request);
    }
}
