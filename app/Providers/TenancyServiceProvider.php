<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    public static string $controllerNamespace = '';

    public function boot(): void
    {
        $this->registerEventListeners();
        $this->registerTenantRoutes();
        $this->prioritizeTenancyMiddleware();
    }

    protected function registerEventListeners(): void
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    protected function events(): array
    {
        return [
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                ])->send(static fn (Events\TenantCreated $event) => $event->tenant)
                    ->shouldBeQueued(false)
                    ->toListener(),
            ],

            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(static fn (Events\TenantDeleted $event) => $event->tenant)
                    ->shouldBeQueued(false)
                    ->toListener(),
            ],

            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\DatabaseMigrated::class => [
                function (Events\DatabaseMigrated $event): void {
                    $event->tenant->run(function (): void {
                        $this->seedTenantRoles();
                    });
                },
            ],
        ];
    }

    protected function registerTenantRoutes(): void
    {
        $tenantRoutesPath = base_path('routes/tenant.php');

        if (! file_exists($tenantRoutesPath)) {
            return;
        }

        Route::namespace(static::$controllerNamespace)->group($tenantRoutesPath);
    }

    protected function prioritizeTenancyMiddleware(): void
    {
        $middleware = [
            Middleware\PreventAccessFromCentralDomains::class,
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($middleware) as $middlewareClass) {
            $this->app->make(Kernel::class)->prependToMiddlewarePriority($middlewareClass);
        }
    }

    protected function seedTenantRoles(): void
    {
        $permissionRegistrarClass = \Spatie\Permission\PermissionRegistrar::class;
        $roleModelClass = \Spatie\Permission\Models\Role::class;

        if (! class_exists($permissionRegistrarClass) || ! class_exists($roleModelClass)) {
            return;
        }

        if (! Schema::hasTable('roles')) {
            return;
        }

        $permissionRegistrar = app($permissionRegistrarClass);

        if (method_exists($permissionRegistrar, 'forgetCachedPermissions')) {
            $permissionRegistrar->forgetCachedPermissions();
        }

        foreach ([
            'tenant_admin',
            'branch_manager',
            'loan_officer',
            'cashier',
            'viewer',
        ] as $role) {
            $roleModelClass::findOrCreate($role, 'web');
        }
    }
}
