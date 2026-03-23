<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    Mockery::close();
});

test('dashboard controller builds cooperative operations metrics without the sales domain', function () {
    Auth::setUser(new GenericUser([
        'id' => 1,
        'tenant_id' => 99,
        'role' => 'admin',
        'name' => 'Tenant Admin',
    ]));

    $branchCountQuery = Mockery::mock();
    $branchCountQuery->shouldReceive('where')->once()->with('tenant_id', 99)->andReturnSelf();
    $branchCountQuery->shouldReceive('count')->once()->andReturn(2);

    $activeBranchCountQuery = Mockery::mock();
    $activeBranchCountQuery->shouldReceive('where')->once()->with('tenant_id', 99)->andReturnSelf();
    $activeBranchCountQuery->shouldReceive('where')->once()->with('is_active', true)->andReturnSelf();
    $activeBranchCountQuery->shouldReceive('count')->once()->andReturn(1);

    $branchOverviewQuery = Mockery::mock();
    $branchOverviewQuery->shouldReceive('where')->once()->with('tenant_id', 99)->andReturnSelf();
    $branchOverviewQuery->shouldReceive('latest')->once()->andReturnSelf();
    $branchOverviewQuery->shouldReceive('limit')->once()->with(5)->andReturnSelf();
    $branchOverviewQuery->shouldReceive('get')->once()->andReturn(collect([
        (object) [
            'name' => 'Main Branch',
            'phone' => '123-4567',
            'is_active' => true,
        ],
    ]));

    $branchAlias = Mockery::mock('alias:App\Models\Branch');
    $branchAlias->shouldReceive('query')->times(3)->andReturn(
        $branchCountQuery,
        $activeBranchCountQuery,
        $branchOverviewQuery
    );

    $userCountQuery = Mockery::mock();
    $userCountQuery->shouldReceive('where')->once()->with('tenant_id', 99)->andReturnSelf();
    $userCountQuery->shouldReceive('count')->once()->andReturn(2);

    $adminUserCountQuery = Mockery::mock();
    $adminUserCountQuery->shouldReceive('where')->once()->with('tenant_id', 99)->andReturnSelf();
    $adminUserCountQuery->shouldReceive('where')->once()->with('role', 'admin')->andReturnSelf();
    $adminUserCountQuery->shouldReceive('count')->once()->andReturn(1);

    $recentUsersQuery = Mockery::mock();
    $recentUsersQuery->shouldReceive('with')->once()->with('branch')->andReturnSelf();
    $recentUsersQuery->shouldReceive('where')->once()->with('tenant_id', 99)->andReturnSelf();
    $recentUsersQuery->shouldReceive('latest')->once()->andReturnSelf();
    $recentUsersQuery->shouldReceive('limit')->once()->with(5)->andReturnSelf();
    $recentUsersQuery->shouldReceive('get')->once()->andReturn(collect([
        (object) [
            'name' => 'Tenant Admin',
            'role' => 'admin',
            'branch' => (object) ['name' => 'Main Branch'],
            'email' => 'admin@example.test',
        ],
    ]));

    $userAlias = Mockery::mock('alias:App\Models\User');
    $userAlias->shouldReceive('query')->times(3)->andReturn(
        $userCountQuery,
        $adminUserCountQuery,
        $recentUsersQuery
    );

    $view = app(DashboardController::class)->index();
    $data = $view->getData();
    $html = $view->render();

    expect($view->name())->toBe('dashboard');
    expect($data['totalBranches'])->toBe(2);
    expect($data['activeBranches'])->toBe(1);
    expect($data['totalUsers'])->toBe(2);
    expect($data['adminUsers'])->toBe(1);
    expect($html)->toContain('Total Branches');
    expect($html)->toContain('Active Branches');
    expect($html)->toContain('Total Users');
    expect($html)->toContain('Admin Users');
    expect($html)->toContain('Recent Users');
    expect($html)->toContain('Branch Status');
    expect($html)->not->toContain('Sales Report');
    expect($html)->not->toContain('Credits Report');
    expect($html)->not->toContain('Stock Report');
});

test('legacy sales routes are no longer registered', function () {
    expect(Route::has('customers.index'))->toBeFalse();
    expect(Route::has('products.index'))->toBeFalse();
    expect(Route::has('sales.index'))->toBeFalse();
    expect(Route::has('credits.index'))->toBeFalse();
    expect(Route::has('credits.payments.store'))->toBeFalse();
    expect(Route::has('reports.sales'))->toBeFalse();
    expect(Route::has('reports.credits'))->toBeFalse();
    expect(Route::has('reports.products'))->toBeFalse();
});
