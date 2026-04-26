<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantFeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! TenantFeatures::tenantHasFeature($feature)) {
            abort(403, 'This feature is not available on your current plan.');
        }

        return $next($request);
    }
}
