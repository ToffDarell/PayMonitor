<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    private const OVERDUE_ALLOWED_ROUTE_PATTERNS = [
        'billing.portal.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant !== null && $tenant->resolvedPortalStatus() === 'overdue' && $request->routeIs(self::OVERDUE_ALLOWED_ROUTE_PATTERNS)) {
            return $next($request);
        }

        if ($tenant !== null && $tenant->accessBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $tenantHost = $tenant->domains()->value('domain') ?? $request->getHost();
            $tenantName = $tenant->name ?? 'Cooperative';
            $portalStatus = $tenant->resolvedPortalStatus();
            $statusMessage = $tenant->accessBlockedMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $statusMessage.' Contact your administrator.',
                ], 423);
            }

            return response()->view('errors.tenant-suspended', [
                'tenantName' => $tenantName,
                'tenantHost' => $tenantHost,
                'portalStatus' => $portalStatus,
                'statusMessage' => $statusMessage,
            ], 423);
        }

        return $next($request);
    }
}
