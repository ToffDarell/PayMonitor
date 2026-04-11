<?php

use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\TenantPermissionMiddleware;
use App\Http\Middleware\SetTenantContext;
use App\Http\Middleware\TenantRoleMiddleware;
use App\Providers\AuthServiceProvider;
use App\Providers\TenancyServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        AuthServiceProvider::class,
        TenancyServiceProvider::class,
    ], false)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(function (Illuminate\Http\Request $request) {
            $centralDomains = config('tenancy.central_domains', ['localhost', 'developement.localhost', '127.0.0.1']);
            if (in_array($request->getHost(), $centralDomains)) {
                return '/central/dashboard';
            }

            $user = $request->user();

            return $user !== null && method_exists($user, 'preferredTenantLandingPath')
                ? $user->preferredTenantLandingPath()
                : '/dashboard';
        });

        $middleware->redirectGuestsTo(function (Illuminate\Http\Request $request) {
            $host = $request->getHost();
            $centralDomains = config('tenancy.central_domains', ['localhost', '127.0.0.1']);

            if (in_array($host, $centralDomains, true)) {
                return '/login';
            }

            return '/login';
        });

        $middleware->alias([
            'tenant.context' => SetTenantContext::class,
            'tenant.active' => EnsureTenantIsActive::class,
            'tenant.role' => TenantRoleMiddleware::class,
            'tenant.permission' => TenantPermissionMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
