<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('central database contains the expected tenancy tables and columns', function () {
    expect(Schema::hasTable('plans'))->toBeTrue();
    expect(Schema::hasTable('tenants'))->toBeTrue();
    expect(Schema::hasTable('domains'))->toBeTrue();
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable('roles'))->toBeTrue();
    expect(Schema::hasTable('permissions'))->toBeTrue();
    expect(Schema::hasTable('model_has_roles'))->toBeTrue();
    expect(Schema::hasTable('model_has_permissions'))->toBeTrue();
    expect(Schema::hasTable('role_has_permissions'))->toBeTrue();

    expect(Schema::getColumnListing('plans'))->toBe([
        'id',
        'name',
        'price',
        'max_branches',
        'max_users',
        'description',
        'created_at',
        'updated_at',
    ]);

    expect(Schema::getColumnListing('tenants'))->toBe([
        'id',
        'name',
        'email',
        'plan_id',
        'address',
        'admin_name',
        'status',
        'subscription_due_at',
        'created_at',
        'updated_at',
        'data',
    ]);

    expect(Schema::getColumnListing('domains'))->toBe([
        'id',
        'domain',
        'tenant_id',
        'created_at',
        'updated_at',
    ]);

    expect(Schema::getColumnListing('users'))->toBe([
        'id',
        'name',
        'email',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ]);
});

test('central tenancy tables include the required indexes and foreign keys', function () {
    $tenantIndexes = collect(DB::select("PRAGMA index_list('tenants')"))->pluck('name');
    $domainIndexes = collect(DB::select("PRAGMA index_list('domains')"))->pluck('name');

    expect($tenantIndexes)->toContain('tenants_plan_id_index');
    expect($domainIndexes)->toContain('domains_tenant_id_index');

    $tenantForeignKeys = collect(DB::select("PRAGMA foreign_key_list('tenants')"));
    $domainForeignKeys = collect(DB::select("PRAGMA foreign_key_list('domains')"));

    expect($tenantForeignKeys->pluck('table'))->toContain('plans');
    expect($tenantForeignKeys->pluck('from'))->toContain('plan_id');
    expect($domainForeignKeys->pluck('table'))->toContain('tenants');
    expect($domainForeignKeys->pluck('from'))->toContain('tenant_id');
});
