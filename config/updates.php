<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Code Deployment
    |--------------------------------------------------------------------------
    |
    | When disabled (default), tenants only run database migrations when they
    | "update". The server code is assumed to be updated centrally by the
    | super-admin or CI pipeline. Enable these flags only if you want tenants
    | to trigger a full code deployment from GitHub releases.
    |
    */

    'auto_deploy_code' => env('AUTO_DEPLOY_CODE', false),

    'allow_tenant_code_deploy' => env('ALLOW_TENANT_CODE_DEPLOY', false),

    /*
    |--------------------------------------------------------------------------
    | Deployment Settings
    |--------------------------------------------------------------------------
    */

    'deployment' => [
        'backup_before_deploy' => true,
        'run_composer_install' => true,
        'run_npm_build' => false,
        'run_database_migrations' => true,
        'run_tenant_migrations' => true,
        'run_queue_restart' => false,
        'min_disk_space_mb' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | During an update, the tenant enters a scoped maintenance mode so that
    | only the updating tenant is affected. Other tenants continue normally.
    |
    */

    'tenant_maintenance' => [
        'enabled' => true,
        'ttl_minutes' => 60,
        'cache_store' => env('CACHE_DRIVER', 'file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    */

    'backup' => [
        'max_backups' => 10,
        'storage_path' => 'backups/tenants',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smoke Test (Optional)
    |--------------------------------------------------------------------------
    |
    | Run a test suite after deployment to verify the application works.
    |
    */

    'smoke_test' => [
        'enabled' => false,
        'command' => 'php artisan test --filter=SmokeTest',
    ],
];
