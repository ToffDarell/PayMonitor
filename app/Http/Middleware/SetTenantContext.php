<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && tenant() !== null) {
            app()->instance('current_tenant_id', tenant('id'));
        }

        return $next($request);
    }
}
