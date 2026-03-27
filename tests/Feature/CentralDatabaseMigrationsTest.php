<?php

use Illuminate\Support\Facades\Schema;

test('central database contains the expected tenancy tables and columns', function () {
    expect(Schema::hasTable('plans'))->toBeTrue();
    expect(Schema::hasTable('tenants'))->toBeTrue();
    expect(Schema::hasTable('domains'))->toBeTrue();
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable('app_versions'))->toBeTrue();
    expect(Schema::hasTable('tenant_version_acknowledgements'))->toBeTrue();
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

    expect(Schema::getColumnListing('app_versions'))->toEqualCanonicalizing([
        'id',
        'version_number',
        'title',
        'changelog',
        'is_active',
        'released_at',
        'created_at',
        'updated_at',
    ]);

    expect(Schema::getColumnListing('tenant_version_acknowledgements'))->toEqualCanonicalizing([
        'id',
        'tenant_id',
        'version_id',
        'acknowledged_at',
        'created_at',
        'updated_at',
    ]);
});

test('central tenancy tables include the required indexes and foreign keys', function () {
    $tenantIndexes = collect(Schema::getIndexes('tenants'))->pluck('name');
    $domainIndexes = collect(Schema::getIndexes('domains'))->pluck('name');
    $acknowledgementIndexes = collect(Schema::getIndexes('tenant_version_acknowledgements'))->pluck('name');

    expect($tenantIndexes)->toContain('tenants_plan_id_index');
    expect($domainIndexes)->toContain('domains_tenant_id_index');
    expect($acknowledgementIndexes)->toContain('tenant_version_ack_unique');

    $tenantForeignKeys = collect(Schema::getForeignKeys('tenants'));
    $domainForeignKeys = collect(Schema::getForeignKeys('domains'));
    $acknowledgementForeignKeys = collect(Schema::getForeignKeys('tenant_version_acknowledgements'));

    expect($tenantForeignKeys->contains(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === 'plans' && in_array('plan_id', $foreignKey['columns'], true)))->toBeTrue();
    expect($domainForeignKeys->contains(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === 'tenants' && in_array('tenant_id', $foreignKey['columns'], true)))->toBeTrue();
    expect($acknowledgementForeignKeys->contains(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === 'app_versions' && in_array('version_id', $foreignKey['columns'], true)))->toBeTrue();
    expect($acknowledgementForeignKeys->contains(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === 'tenants' && in_array('tenant_id', $foreignKey['columns'], true)))->toBeTrue();
});
