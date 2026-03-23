<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Tenant;
use Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;
use Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager;

$appUrlHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
$configuredDomain = env('APP_DOMAIN', $appUrlHost);

$centralDomains = array_values(array_unique(array_filter([
    $appUrlHost,
    $configuredDomain,
    'localhost',
    '127.0.0.1',
])));

$tenantBaseDomain = collect($centralDomains)
    ->first(static fn (string $domain): bool => $domain !== '127.0.0.1')
    ?? 'localhost';

return [
    'tenant_model' => Tenant::class,
    'id_generator' => null,
    'domain_model' => Domain::class,

    'central_domains' => $centralDomains,

    'tenant_base_domain' => $tenantBaseDomain,

    'bootstrappers' => [
        DatabaseTenancyBootstrapper::class,
        CacheTenancyBootstrapper::class,
        FilesystemTenancyBootstrapper::class,
        QueueTenancyBootstrapper::class,
    ],

    'runtime' => [
        'identification_middleware' => InitializeTenancyByDomain::class,
        'tenancy_listener' => BootstrapTenancy::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant_',
        'suffix' => '',
        'managers' => [
            'sqlite' => SQLiteDatabaseManager::class,
            'mysql' => MySQLDatabaseManager::class,
            'pgsql' => PostgreSQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [],

    'routes' => true,

    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--force' => true,
    ],
];
