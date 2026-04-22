<?php

namespace App\Http\Middleware;

use App\Services\TenantUpdateService;
use Closure;
use Illuminate\Http\Request;

class RequiredUpdateMiddleware
{
    public function __construct(
        private TenantUpdateService $tenantUpdateService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->route('tenant');

        if (!$tenantId) {
            return $next($request);
        }

        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        if ($this->tenantUpdateService->isUpdateRequired($tenantId)) {
            $requiredUpdate = $this->tenantUpdateService->getRequiredUpdate($tenantId);

            return redirect()->route('settings.updates', ['tenant' => $tenantId])
                ->with('error', 'A required update must be applied before continuing.');
        }

        return $next($request);
    }

    private function isExemptRoute(Request $request): bool
    {
        $exemptRoutes = [
            'settings.index',
            'settings.updates',
            'settings.updates.apply',
            'tenant.logout',
        ];

        $currentRoute = $request->route()?->getName();

        return is_string($currentRoute) && in_array($currentRoute, $exemptRoutes, true);
    }
}
