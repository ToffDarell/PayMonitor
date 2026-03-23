<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    \Mockery::close();
});

test('tenancy configuration uses the application tenant and domain models', function () {
    expect(config('tenancy.tenant_model'))->toBe(\App\Models\Tenant::class);
    expect(config('tenancy.domain_model'))->toBe(\App\Models\Domain::class);
    expect(config('tenancy.central_domains'))->toBe([
        'paymonitor.com',
        'localhost',
        '127.0.0.1',
    ]);
});

test('tenant full domain prefers the saved tenant domain', function () {
    Config::set('app.url', 'https://paymonitor.com');

    $tenant = \Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 'alpha';

    $domainsRelation = \Mockery::mock();
    $domainsRelation->shouldReceive('value')->once()->with('domain')->andReturn('alpha.paymonitor.com');
    $tenant->shouldReceive('domains')->once()->andReturn($domainsRelation);

    expect($tenant->getFullDomain())->toBe('https://alpha.paymonitor.com');
});

test('tenant full domain falls back to id and central domain', function () {
    Config::set('app.url', 'https://paymonitor.com');

    $tenant = \Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 'bravo';

    $domainsRelation = \Mockery::mock();
    $domainsRelation->shouldReceive('value')->once()->with('domain')->andReturnNull();
    $tenant->shouldReceive('domains')->once()->andReturn($domainsRelation);

    expect($tenant->getFullDomain())->toBe('https://bravo.paymonitor.com');
});

test('tenant overdue helper compares the subscription date against today', function () {
    $overdueTenant = new Tenant([
        'subscription_due_at' => today()->subDay(),
    ]);

    $currentTenant = new Tenant([
        'subscription_due_at' => today(),
    ]);

    expect($overdueTenant->isOverdue())->toBeTrue();
    expect($currentTenant->isOverdue())->toBeFalse();
});

test('tenant usage counts branches users members loan types and loans in the tenant database', function () {
    Schema::shouldReceive('hasTable')->once()->with('branches')->andReturn(true);
    Schema::shouldReceive('hasTable')->once()->with('users')->andReturn(true);
    Schema::shouldReceive('hasTable')->once()->with('members')->andReturn(true);
    Schema::shouldReceive('hasTable')->once()->with('loan_types')->andReturn(true);
    Schema::shouldReceive('hasTable')->once()->with('loans')->andReturn(true);

    $branchesTable = \Mockery::mock();
    $branchesTable->shouldReceive('count')->once()->andReturn(2);

    $usersTable = \Mockery::mock();
    $usersTable->shouldReceive('count')->once()->andReturn(5);

    $membersTable = \Mockery::mock();
    $membersTable->shouldReceive('count')->once()->andReturn(12);

    $loanTypesTable = \Mockery::mock();
    $loanTypesTable->shouldReceive('count')->once()->andReturn(4);

    $loansTable = \Mockery::mock();
    $loansTable->shouldReceive('count')->once()->andReturn(9);

    DB::shouldReceive('table')->once()->with('branches')->andReturn($branchesTable);
    DB::shouldReceive('table')->once()->with('users')->andReturn($usersTable);
    DB::shouldReceive('table')->once()->with('members')->andReturn($membersTable);
    DB::shouldReceive('table')->once()->with('loan_types')->andReturn($loanTypesTable);
    DB::shouldReceive('table')->once()->with('loans')->andReturn($loansTable);

    $tenant = \Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): int => $callback());

    expect($tenant->getUsage())->toBe(32);
});
