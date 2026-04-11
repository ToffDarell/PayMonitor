<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant !== null && in_array($tenant->status, ['suspended', 'inactive'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $tenantHost = $tenant->domains()->value('domain') ?? $request->getHost();
            $tenantName = $tenant->name ?? 'Cooperative';
            $portalStatus = $tenant->status;
            $statusMessage = match ($portalStatus) {
                'suspended' => 'This cooperative portal is currently suspended.',
                'inactive' => 'This cooperative portal is currently inactive.',
                default => 'This cooperative portal is currently unavailable.',
            };

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
