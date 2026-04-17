<?php

use Illuminate\Support\Facades\Schema;

test('central database contains the expected tenancy tables and columns', function () {
    expect(Schema::hasTable('plans'))->toBeTrue();
    expect(Schema::hasTable('tenants'))->toBeTrue();
    expect(Schema::hasTable('domains'))->toBeTrue();
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable('app_versions'))->toBeFalse();
    expect(Schema::hasTable('tenant_version_acknowledgements'))->toBeFalse();
    expect(Schema::hasTable('roles'))->toBeTrue();
    expect(Schema::hasTable('permissions'))->toBeTrue();
    expect(Schema::hasTable('model_has_roles'))->toBeTrue();
    expect(Schema::hasTable('model_has_permissions'))->toBeTrue();
    expect(Schema::hasTable('role_has_permissions'))->toBeTrue();

    expect(Schema::getColumnListing('plans'))->toEqualCanonicalizing([
        'id',
        'name',
        'price',
        'max_branches',
        'max_users',
        'description',
        'created_at',
        'updated_at',
    ]);

    expect(Schema::getColumnListing('tenants'))->toEqualCanonicalizing([
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

    expect(Schema::getColumnListing('domains'))->toEqualCanonicalizing([
        'id',
        'domain',
        'tenant_id',
        'created_at',
        'updated_at',
    ]);

    expect(Schema::getColumnListing('users'))->toEqualCanonicalizing([
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
    $tenantIndexes = collect(Schema::getIndexes('tenants'))->pluck('name');
    $domainIndexes = collect(Schema::getIndexes('domains'))->pluck('name');

    expect($tenantIndexes)->toContain('tenants_plan_id_index');
    expect($domainIndexes)->toContain('domains_tenant_id_index');

    $tenantForeignKeys = collect(Schema::getForeignKeys('tenants'));
    $domainForeignKeys = collect(Schema::getForeignKeys('domains'));

    expect($tenantForeignKeys->contains(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === 'plans' && in_array('plan_id', $foreignKey['columns'], true)))->toBeTrue();
    expect($domainForeignKeys->contains(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === 'tenants' && in_array('tenant_id', $foreignKey['columns'], true)))->toBeTrue();
});
