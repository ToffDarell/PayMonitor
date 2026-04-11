<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null || ! method_exists($user, 'hasAnyTenantPermission') || ! $user->hasAnyTenantPermission($permissions)) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
