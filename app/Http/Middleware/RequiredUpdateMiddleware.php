<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
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
        $tenantId = (string) (tenant()?->id ?? $request->route('tenant'));

        if ($tenantId === '') {
            return $next($request);
        }

        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        if ($this->tenantUpdateService->isUpdateRequired($tenantId) || $this->tenantFlagRequiresUpdate($tenantId)) {
            return redirect()->route('settings.updates', $this->tenantRouteParameters($tenantId), false)
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

    private function tenantFlagRequiresUpdate(string $tenantId): bool
    {
        $tenantModel = config('tenancy.tenant_model', Tenant::class);
        $tenant = $tenantModel::query()->find($tenantId);

        return (bool) ($tenant?->update_required ?? false);
    }

    /**
     * @return array<string, string>
     */
    private function tenantRouteParameters(string $tenantId): array
    {
        $currentTenant = request()->route('tenant');

        if (is_string($currentTenant) && $currentTenant !== '') {
            return ['tenant' => $currentTenant];
        }

        $host = request()->getHost();
        $centralDomains = config('tenancy.central_domains', ['localhost']);

        foreach ($centralDomains as $domain) {
            $domain = (string) $domain;

            if ($domain !== '' && str_ends_with($host, '.'.$domain)) {
                return ['tenant' => $tenantId];
            }
        }

        return ['tenant' => $tenantId];
    }
}
