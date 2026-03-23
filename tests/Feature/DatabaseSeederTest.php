<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (['alpha', 'bravo'] as $tenantId) {
        $databasePath = database_path("tenant_{$tenantId}");

        if (File::exists($databasePath)) {
            File::delete($databasePath);
        }
    }
});

afterEach(function (): void {
    foreach (['alpha', 'bravo'] as $tenantId) {
        $databasePath = database_path("tenant_{$tenantId}");

        if (File::exists($databasePath)) {
            File::delete($databasePath);
        }
    }
});

test('database seeder provisions the central app and both demo tenants', function (): void {
    $this->seed();

    expect(Role::query()->where('name', 'super_admin')->exists())->toBeTrue();

    $plans = Plan::query()
        ->orderBy('price')
        ->get()
        ->keyBy('name');

    expect($plans)->toHaveCount(3);
    expect((float) $plans['Basic']->price)->toBe(499.0);
    expect($plans['Basic']->max_branches)->toBe(2);
    expect($plans['Basic']->max_users)->toBe(10);
    expect((float) $plans['Standard']->price)->toBe(999.0);
    expect((float) $plans['Premium']->price)->toBe(1999.0);
    expect($plans['Premium']->max_branches)->toBe(0);
    expect($plans['Premium']->max_users)->toBe(0);

    $superAdmin = User::query()->where('email', 'superadmin@paymonitor.com')->first();

    expect($superAdmin)->not->toBeNull();
    expect($superAdmin?->hasRole('super_admin'))->toBeTrue();

    $alphaTenant = Tenant::query()->where('id', 'alpha')->first();
    $bravoTenant = Tenant::query()->where('id', 'bravo')->first();

    expect($alphaTenant)->not->toBeNull();
    expect($alphaTenant?->domains()->where('domain', 'alpha.paymonitor.com')->exists())->toBeTrue();
    expect($bravoTenant)->not->toBeNull();
    expect($bravoTenant?->domains()->where('domain', 'bravo.paymonitor.com')->exists())->toBeTrue();

    $alphaTenant?->run(function (): void {
        expect(Role::query()->where('name', 'tenant_admin')->exists())->toBeTrue();
        expect(User::role('tenant_admin')->count())->toBe(1);
        expect(Branch::query()->where('name', 'Main Branch - Malaybalay')->exists())->toBeTrue();
        expect(LoanType::query()->count())->toBe(2);
        expect(Member::query()->count())->toBe(2);
        expect(Member::query()->where('member_number', 'MBR-20260101-0001')->exists())->toBeTrue();
        expect(Member::query()->where('member_number', 'MBR-20260101-0002')->exists())->toBeTrue();

        $loan = Loan::query()->with('loanPayments')->first();

        expect($loan)->not->toBeNull();
        expect($loan?->loanType?->name)->toBe('Money Loan');
        expect((float) $loan?->principal_amount)->toBe(10000.0);
        expect($loan?->term_months)->toBe(6);
        expect(LoanPayment::query()->count())->toBe(1);
        expect(round((float) $loan?->amount_paid, 2))->toBe(1833.33);
    });

    $bravoTenant?->run(function (): void {
        expect(Branch::query()->where('name', 'Main Branch - Valencia')->exists())->toBeTrue();
        expect(LoanType::query()->count())->toBe(2);
        expect(Member::query()->count())->toBe(2);

        $loan = Loan::query()->with('loanPayments')->first();

        expect($loan)->not->toBeNull();
        expect($loan?->status)->toBe('overdue');
        expect($loan?->due_date?->isPast())->toBeTrue();
        expect(LoanPayment::query()->count())->toBe(1);
    });
});
