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
        // Tenant now comes from the verified, logged-in user — never from a
        // client-supplied header. auth:sanctum always runs before this
        // middleware (see routes/api.php), so $request->user() is trusted.
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            abort(403, 'This account is not linked to a tenant.');
        }

        $tenant = Tenant::find($user->tenant_id);

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        $this->tenantContext->set($tenant);

        return $next($request);
    }
}
