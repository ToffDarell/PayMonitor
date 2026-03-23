<?php

declare(strict_types=1);

use App\Http\Requests\Central\StorePlanRequest;
use App\Http\Requests\Central\StoreTenantRequest;
use App\Http\Requests\Central\UpdateTenantRequest;
use App\Http\Requests\Tenant\StoreBranchRequest;
use App\Http\Requests\Tenant\StoreLoanPaymentRequest;
use App\Http\Requests\Tenant\StoreLoanRequest;
use App\Http\Requests\Tenant\StoreLoanTypeRequest;
use App\Http\Requests\Tenant\StoreMemberRequest;
use App\Http\Requests\Tenant\StoreUserRequest;
use App\Http\Requests\Tenant\UpdateUserRequest;
use App\Mail\TenantWelcomeMail;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LoanService;
use App\Services\TenantService;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    app()->forgetInstance(TenantContract::class);
    \Mockery::close();
});

beforeEach(function (): void {
    Artisan::call('migrate:fresh', [
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
    ]);
});

test('loan service computes flat loan values', function (): void {
    $service = new LoanService;

    $computed = $service->computeLoan([
        'principal_amount' => 1000,
        'interest_rate' => 5,
        'interest_type' => 'flat',
        'term_months' => 2,
    ]);

    expect($computed)->toBe([
        'total_interest' => 100.0,
        'total_payable' => 1100.0,
        'monthly_payment' => 550.0,
        'outstanding_balance' => 1100.0,
    ]);
});

test('loan service generates amortization schedules and marks overdue loans', function (): void {
    $branch = Branch::create([
        'name' => 'Main Branch',
        'address' => 'Central Office',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Loan Officer',
        'email' => 'officer@example.com',
        'password' => 'secret-password',
        'branch_id' => $branch->id,
    ]);

    $member = Member::create([
        'branch_id' => $branch->id,
        'member_number' => 'MBR-0001',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'is_active' => true,
        'joined_at' => today()->toDateString(),
    ]);

    $loanType = LoanType::create([
        'name' => 'Money Loan',
        'description' => 'Short-term money loan',
        'interest_rate' => 5,
        'interest_type' => 'flat',
        'max_term_months' => 12,
        'min_amount' => 500,
        'max_amount' => 10000,
        'is_active' => true,
    ]);

    $loanService = new LoanService;
    $computed = $loanService->computeLoan([
        'principal_amount' => 1000,
        'interest_rate' => 5,
        'interest_type' => 'flat',
        'term_months' => 2,
    ]);

    $loan = Loan::create([
        'member_id' => $member->id,
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'loan_type_id' => $loanType->id,
        'loan_number' => 'LN-20260318-1234',
        'principal_amount' => 1000,
        'interest_rate' => 5,
        'interest_type' => 'flat',
        'term_months' => 2,
        'total_interest' => $computed['total_interest'],
        'total_payable' => $computed['total_payable'],
        'monthly_payment' => $computed['monthly_payment'],
        'amount_paid' => 0,
        'outstanding_balance' => $computed['outstanding_balance'],
        'status' => 'active',
        'release_date' => today()->subMonths(3)->toDateString(),
        'due_date' => today()->subDay()->toDateString(),
        'notes' => 'Test loan',
    ]);

    $loanService->generateAmortizationSchedule($loan);
    $loanService->markOverdueLoans();

    expect($loan->loanSchedules()->count())->toBe(2);
    expect($loan->fresh()->status)->toBe('overdue');
});

test('tenant service toggles tenant status', function (): void {
    $service = new TenantService;

    $tenant = \Mockery::mock(Tenant::class)->makePartial();
    $tenant->status = 'active';
    $tenant->shouldReceive('save')->twice()->andReturnTrue();

    $service->suspendTenant($tenant);
    expect($tenant->status)->toBe('suspended');

    $service->activateTenant($tenant);
    expect($tenant->status)->toBe('active');
});

test('tenant service returns tenant usage counts', function (): void {
    $branch = Branch::create([
        'name' => 'Main Branch',
        'address' => 'Central Office',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Tenant Admin',
        'email' => 'admin@example.com',
        'password' => 'secret-password',
        'branch_id' => null,
    ]);

    $member = Member::create([
        'branch_id' => null,
        'member_number' => 'MBR-0002',
        'first_name' => 'Luis',
        'last_name' => 'Rivera',
        'is_active' => true,
        'joined_at' => today()->toDateString(),
    ]);

    $loanType = LoanType::create([
        'name' => 'Appliance Loan',
        'interest_rate' => 4,
        'interest_type' => 'flat',
        'is_active' => true,
    ]);

    Loan::create([
        'member_id' => $member->id,
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'loan_type_id' => $loanType->id,
        'loan_number' => 'LN-20260318-5678',
        'principal_amount' => 5000,
        'interest_rate' => 4,
        'interest_type' => 'flat',
        'term_months' => 5,
        'total_interest' => 1000,
        'total_payable' => 6000,
        'monthly_payment' => 1200,
        'amount_paid' => 0,
        'outstanding_balance' => 6000,
        'status' => 'active',
        'release_date' => today()->toDateString(),
        'due_date' => today()->addMonths(5)->toDateString(),
        'notes' => null,
    ]);

    $tenant = \Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): array => $callback());

    $usage = (new TenantService)->getTenantUsage($tenant);

    expect($usage)->toBe([
        'branches' => 1,
        'users' => 1,
        'members' => 1,
        'loan_types' => 1,
        'loans' => 1,
        'total' => 5,
    ]);
});

test('audit service stores records only for premium tenants', function (): void {
    $user = User::create([
        'name' => 'Cashier',
        'email' => 'cashier@example.com',
        'password' => 'secret-password',
        'branch_id' => null,
    ]);

    $this->actingAs($user);

    $tenant = new Tenant([
        'id' => 'premium-coop',
        'name' => 'Premium Cooperative',
        'email' => 'admin@premium.test',
    ]);
    $tenant->setRelation('plan', new Plan(['name' => 'Premium']));

    app()->instance(TenantContract::class, $tenant);

    $member = Member::create([
        'branch_id' => null,
        'member_number' => 'MBR-0003',
        'first_name' => 'Mina',
        'last_name' => 'Cruz',
        'is_active' => true,
        'joined_at' => today()->toDateString(),
    ]);

    (new AuditService)->log('updated', $member, ['first_name' => 'Mina'], ['first_name' => 'Maria']);

    expect(AuditLog::query()->count())->toBe(1);
    expect(AuditLog::query()->first()?->action)->toBe('updated');
    expect(AuditLog::query()->first()?->model)->toBe('Member');
});

test('tenant welcome mail renders the credentials email', function (): void {
    $tenant = new Tenant([
        'id' => 'alpha',
        'name' => 'Alpha Cooperative',
        'email' => 'admin@alpha.test',
    ]);

    $mail = new TenantWelcomeMail(
        $tenant,
        'admin@alpha.test',
        'TempPass1234',
        'https://alpha.paymonitor.com/login',
    );

    expect($mail->envelope()->subject)->toBe('Your PayMonitor Account Has Been Created');
    expect($mail->render())->toContain('Alpha Cooperative');
    expect($mail->render())->toContain('Your PayMonitor lending system account is ready.');
    expect($mail->render())->toContain('TempPass1234');
});

test('generated form requests authorize and expose the expected fields', function (): void {
    $expectedRules = [
        StoreTenantRequest::class => ['name', 'address', 'domain', 'admin_name', 'admin_email', 'plan_id', 'subscription_due_at'],
        UpdateTenantRequest::class => ['name', 'address', 'plan_id', 'subscription_due_at', 'status'],
        StorePlanRequest::class => ['name', 'price', 'max_branches', 'max_users'],
        StoreMemberRequest::class => ['branch_id', 'first_name', 'last_name', 'middle_name', 'phone', 'email', 'birthdate', 'gender', 'civil_status', 'address', 'occupation', 'joined_at', 'is_active'],
        StoreLoanTypeRequest::class => ['name', 'interest_rate', 'interest_type', 'max_term_months', 'min_amount', 'max_amount'],
        StoreLoanRequest::class => ['member_id', 'branch_id', 'loan_type_id', 'principal_amount', 'term_months', 'release_date', 'notes'],
        StoreLoanPaymentRequest::class => ['loan_id', 'amount', 'payment_date', 'period_covered', 'notes'],
        StoreBranchRequest::class => ['name', 'address', 'is_active'],
        StoreUserRequest::class => ['name', 'email', 'generated_password', 'role', 'branch_id'],
        UpdateUserRequest::class => ['name', 'role', 'branch_id'],
    ];

    foreach ($expectedRules as $requestClass => $expectedKeys) {
        $request = new $requestClass;

        expect($request->authorize())->toBeTrue();
        expect(array_keys($request->rules()))->toBe($expectedKeys);
    }
});
