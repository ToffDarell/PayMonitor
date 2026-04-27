<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * RequiredUpdateMiddleware
 *
 * NOTE: This middleware intentionally does NOT redirect users when an update
 * is required. Enforcement is handled entirely by the blocking modal overlay
 * in resources/views/layouts/tenant.blade.php, which renders on every
 * authenticated page and cannot be dismissed until the portal is updated.
 *
 * A hard redirect here would prevent the modal from ever rendering, because
 * the redirect fires before the blade layout is processed.
 */
class RequiredUpdateMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Pass through — the tenant layout blade handles update enforcement
        // via a non-dismissible full-screen modal overlay.
        return $next($request);
    }
}
