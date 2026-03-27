<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Domain;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class);

beforeEach(function (): void {
    $this->tenantDatabasePaths = [];

    $centralMigrationPaths = collect(File::files(database_path('migrations')))
        ->map(static fn (SplFileInfo $file): string => $file->getPathname())
        ->all();

    Artisan::call('migrate:fresh', [
        '--path' => $centralMigrationPaths,
        '--realpath' => true,
        '--force' => true,
    ]);
});

afterEach(function (): void {
    foreach ($this->tenantDatabasePaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

function tenantUrl(string $tenantId = 'alpha', string $path = '/'): string
{
    $normalizedPath = str_starts_with($path, '/') ? $path : "/{$path}";

    return "http://{$tenantId}.localhost{$normalizedPath}";
}

function provisionTenant(string $tenantId = 'alpha', string $status = 'active'): Tenant
{
    $plan = Plan::query()->create([
        'name' => "Plan {$tenantId}",
        'price' => 999,
        'max_branches' => 0,
        'max_users' => 0,
    ]);

    $tenant = Tenant::withoutEvents(static fn (): Tenant => Tenant::query()->create([
        'id' => $tenantId,
        'name' => ucfirst($tenantId).' Cooperative',
        'email' => "{$tenantId}@example.com",
        'plan_id' => $plan->id,
        'status' => $status,
        'subscription_due_at' => today()->addMonth(),
    ]));

    Domain::query()->create([
        'domain' => "{$tenantId}.localhost",
        'tenant_id' => $tenant->id,
    ]);

    $databasePath = database_path($tenant->database()->getName());

    if (File::exists($databasePath)) {
        File::delete($databasePath);
    }

    if (! File::exists($databasePath)) {
        File::put($databasePath, '');
    }

    test()->tenantDatabasePaths = [
        ...test()->tenantDatabasePaths,
        $databasePath,
    ];

    Artisan::call('tenants:migrate', [
        '--tenants' => [$tenant->id],
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
        '--force' => true,
    ]);

    $tenant->run(static function (): void {
        foreach (['tenant_admin', 'branch_manager', 'loan_officer', 'cashier', 'viewer'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    });

    return $tenant;
}

function createTenantUser(Tenant $tenant, string $role = 'tenant_admin', array $attributes = []): User
{
    return $tenant->run(static function () use ($role, $attributes): User {
        $user = User::query()->create(array_merge([
            'name' => 'Tenant User',
            'email' => "{$role}@example.com",
            'password' => 'password123',
        ], $attributes));

        $user->assignRole($role);

        return $user;
    });
}

test('tenant login rejects suspended accounts', function (): void {
    $tenant = provisionTenant('suspended', 'suspended');
    createTenantUser($tenant, 'tenant_admin', ['email' => 'suspended@example.com']);

    $this->from(tenantUrl('suspended', '/login'))
        ->post(tenantUrl('suspended', '/login'), [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'This account has been suspended. Contact support.']);

    $this->assertGuest();
});

test('tenant dashboard shows lending metrics', function (): void {
    $tenant = provisionTenant();
    $user = createTenantUser($tenant, 'tenant_admin');

    $tenant->run(function (): void {
        $branch = Branch::query()->create([
            'name' => 'Main Branch',
            'address' => 'Main Street',
            'is_active' => true,
        ]);

        $member = Member::query()->create([
            'branch_id' => $branch->id,
            'member_number' => 'MBR-20260318-1001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'is_active' => true,
        ]);

        $loanType = LoanType::query()->create([
            'name' => 'Money Loan',
            'interest_rate' => 5,
            'interest_type' => 'flat',
            'is_active' => true,
        ]);

        $loan = Loan::query()->create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'user_id' => User::query()->firstOrFail()->id,
            'loan_type_id' => $loanType->id,
            'loan_number' => 'LN-20260318-1001',
            'principal_amount' => 1000,
            'interest_rate' => 5,
            'interest_type' => 'flat',
            'term_months' => 3,
            'total_interest' => 150,
            'total_payable' => 1150,
            'monthly_payment' => 383.33,
            'amount_paid' => 100,
            'outstanding_balance' => 1050,
            'status' => 'overdue',
            'release_date' => today()->subMonths(2),
            'due_date' => today()->subDay(),
        ]);

        LoanPayment::query()->create([
            'loan_id' => $loan->id,
            'user_id' => User::query()->firstOrFail()->id,
            'amount' => 100,
            'payment_date' => today(),
        ]);
    });

    actingAs($user);

    $this->get(tenantUrl('alpha', '/dashboard'))
        ->assertOk()
        ->assertSee('LN-20260318-1001')
        ->assertSee('1,050.00');
});

test('branch pages render for authorized tenant users', function (): void {
    $tenant = provisionTenant('branching');
    $user = createTenantUser($tenant, 'tenant_admin');

    $branch = $tenant->run(static function (): Branch {
        return Branch::query()->create([
            'name' => 'North Branch',
            'address' => 'North Street',
            'is_active' => true,
        ]);
    });

    actingAs($user);

    $this->get(tenantUrl('branching', '/branches'))
        ->assertOk()
        ->assertSee('Branches')
        ->assertSee('North Branch')
        ->assertSee('Staff Count');

    $this->get(tenantUrl('branching', '/branches/create'))
        ->assertOk()
        ->assertSee('Add Branch');

    $this->get(tenantUrl('branching', '/branches/'.$branch->id))
        ->assertOk()
        ->assertSee('Branch Details')
        ->assertSee('Outstanding Balance');

    $this->get(tenantUrl('branching', '/branches/'.$branch->id.'/edit'))
        ->assertOk()
        ->assertSee('Edit Branch');
});

test('tenant user store creates staff account and shows generated password', function (): void {
    $tenant = provisionTenant('staffing');
    $user = createTenantUser($tenant, 'tenant_admin');
    Mail::fake();

    $branch = $tenant->run(static function (): Branch {
        return Branch::query()->create([
            'name' => 'Operations Branch',
            'address' => 'Ops Street',
            'is_active' => true,
        ]);
    });

    actingAs($user);

    $this->get(tenantUrl('staffing', '/users/create'))
        ->assertOk()
        ->assertSee('Create User')
        ->assertSee('Copy Password')
        ->assertSee('Save this password');

    $this->post(tenantUrl('staffing', '/users'), [
        'name' => 'Cashier User',
        'email' => 'cashier@example.com',
        'generated_password' => 'TEMP123ABC',
        'role' => 'cashier',
        'branch_id' => $branch->id,
    ])->assertOk()
        ->assertSee('TEMP123ABC')
        ->assertSee('Login credentials have also been sent to cashier@example.com.');

    $tenant->run(static function (): void {
        $createdUser = User::query()->where('email', 'cashier@example.com')->firstOrFail();

        expect($createdUser->branch_id)->not->toBeNull();
        expect($createdUser->hasRole('cashier'))->toBeTrue();
        expect(Hash::check('TEMP123ABC', $createdUser->password))->toBeTrue();
    });

    Mail::assertSent(\App\Mail\TenantWelcomeMail::class, function ($mail) use ($tenant): bool {
        return $mail->hasTo('cashier@example.com')
            && $mail->email === 'cashier@example.com'
            && $mail->password === 'TEMP123ABC'
            && $mail->tenant->is($tenant)
            && str_contains($mail->loginUrl, 'http://staffing.localhost/login');
    });

    $createdUserId = $tenant->run(static fn (): int => User::query()->where('email', 'cashier@example.com')->value('id'));

    $this->get(tenantUrl('staffing', '/users'))
        ->assertOk()
        ->assertSee('Users')
        ->assertSee('Cashier User');

    $this->get(tenantUrl('staffing', '/users/'.$createdUserId))
        ->assertOk()
        ->assertSee('User Profile')
        ->assertSee('Cashier User');

    $this->get(tenantUrl('staffing', '/users/'.$createdUserId.'/edit'))
        ->assertOk()
        ->assertSee('Edit User')
        ->assertSee('Active / Inactive');
});

test('member store generates a membership number', function (): void {
    $tenant = provisionTenant('members');
    $user = createTenantUser($tenant, 'loan_officer');

    $branch = $tenant->run(static function (): Branch {
        return Branch::query()->create([
            'name' => 'Member Branch',
            'address' => 'Member Street',
            'is_active' => true,
        ]);
    });

    actingAs($user);

    $this->post(tenantUrl('members', '/members'), [
        'branch_id' => $branch->id,
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'phone' => '09123456789',
        'joined_at' => today()->toDateString(),
    ])->assertRedirect();

    $tenant->run(static function (): void {
        $member = Member::query()->firstOrFail();

        expect($member->member_number)->toStartWith('MBR-');
    });
});

test('member management pages render tenant borrower views', function (): void {
    $tenant = provisionTenant('member-pages');
    $user = createTenantUser($tenant, 'tenant_admin');

    $memberId = $tenant->run(function (): int {
        $branch = Branch::query()->create([
            'name' => 'Members Branch',
            'address' => 'Member Street',
            'is_active' => true,
        ]);

        $member = Member::query()->create([
            'branch_id' => $branch->id,
            'member_number' => 'MBR-20260318-4001',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'phone' => '09170000001',
            'is_active' => true,
            'joined_at' => today(),
        ]);

        $loanType = LoanType::query()->create([
            'name' => 'Salary Loan',
            'interest_rate' => 5,
            'interest_type' => 'flat',
            'is_active' => true,
        ]);

        Loan::query()->create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'user_id' => User::query()->firstOrFail()->id,
            'loan_type_id' => $loanType->id,
            'loan_number' => 'LN-20260318-4001',
            'principal_amount' => 3000,
            'interest_rate' => 5,
            'interest_type' => 'flat',
            'term_months' => 6,
            'total_interest' => 900,
            'total_payable' => 3900,
            'monthly_payment' => 650,
            'amount_paid' => 650,
            'outstanding_balance' => 3250,
            'status' => 'active',
            'release_date' => today()->subMonth(),
            'due_date' => today()->addMonths(5),
        ]);

        return $member->id;
    });

    actingAs($user);

    $this->get(tenantUrl('member-pages', '/members'))
        ->assertOk()
        ->assertSee('Members')
        ->assertSee('MBR-20260318-4001');

    $this->get(tenantUrl('member-pages', '/members/'.$memberId))
        ->assertOk()
        ->assertSee('Member Profile')
        ->assertSee('Loan History')
        ->assertSee('LN-20260318-4001');

    $this->get(tenantUrl('member-pages', '/members/'.$memberId.'/edit'))
        ->assertOk()
        ->assertSee('Edit Member')
        ->assertSee('Active / Inactive');
});

test('loan preview endpoint returns computed values', function (): void {
    $tenant = provisionTenant('preview');
    $user = createTenantUser($tenant, 'loan_officer');

    actingAs($user);

    $this->postJson(tenantUrl('preview', '/loans/compute-preview'), [
        'principal' => 1000,
        'rate' => 5,
        'type' => 'flat',
        'term_months' => 3,
    ])
        ->assertOk()
        ->assertJson([
            'total_interest' => 150,
            'total_payable' => 1150,
        ]);
});

test('loan type and loan pages render tenant lending views', function (): void {
    $tenant = provisionTenant('loan-pages');
    $user = createTenantUser($tenant, 'tenant_admin');

    $tenantData = $tenant->run(function (): array {
        $branch = Branch::query()->create([
            'name' => 'Loans Branch',
            'address' => 'Loans Street',
            'is_active' => true,
        ]);

        $member = Member::query()->create([
            'branch_id' => $branch->id,
            'member_number' => 'MBR-20260318-4002',
            'first_name' => 'Paolo',
            'last_name' => 'Rivera',
            'is_active' => true,
        ]);

        $loanType = LoanType::query()->create([
            'name' => 'Emergency Loan',
            'interest_rate' => 4,
            'interest_type' => 'flat',
            'max_term_months' => 12,
            'min_amount' => 500,
            'max_amount' => 10000,
            'is_active' => true,
        ]);

        $loan = Loan::query()->create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'user_id' => User::query()->firstOrFail()->id,
            'loan_type_id' => $loanType->id,
            'loan_number' => 'LN-20260318-4002',
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
            'release_date' => today(),
            'due_date' => today()->addMonths(5),
        ]);

        LoanSchedule::query()->create([
            'loan_id' => $loan->id,
            'period_number' => 1,
            'due_date' => today()->addMonth(),
            'amount_due' => 1200,
            'principal_portion' => 1000,
            'interest_portion' => 200,
            'status' => 'pending',
        ]);

        return [
            'loan_type_id' => $loanType->id,
            'loan_id' => $loan->id,
        ];
    });

    actingAs($user);

    $this->get(tenantUrl('loan-pages', '/loan-types'))
        ->assertOk()
        ->assertSee('Loan Types')
        ->assertSee('Emergency Loan');

    $this->get(tenantUrl('loan-pages', '/loan-types/create'))
        ->assertOk()
        ->assertSee('Add Loan Type')
        ->assertSee('Interest Type');

    $this->get(tenantUrl('loan-pages', '/loan-types/'.$tenantData['loan_type_id'].'/edit'))
        ->assertOk()
        ->assertSee('Edit Loan Type')
        ->assertSee('Emergency Loan');

    $this->get(tenantUrl('loan-pages', '/loans'))
        ->assertOk()
        ->assertSee('Loans')
        ->assertSee('LN-20260318-4002');

    $this->get(tenantUrl('loan-pages', '/loans/create'))
        ->assertOk()
        ->assertSee('Loan Computation Preview')
        ->assertSee('Release Loan');

    $this->get(tenantUrl('loan-pages', '/loans/'.$tenantData['loan_id']))
        ->assertOk()
        ->assertSee('Amortization Schedule')
        ->assertSee('Payment History');

    $this->get(tenantUrl('loan-pages', '/loans/'.$tenantData['loan_id'].'/edit'))
        ->assertOk()
        ->assertSee('Edit Loan')
        ->assertSee('Update Loan');
});

test('loan payment recording updates the loan and schedule', function (): void {
    $tenant = provisionTenant('payments');
    $user = createTenantUser($tenant, 'cashier');

    $loan = $tenant->run(function (): Loan {
        $branch = Branch::query()->create([
            'name' => 'Payments Branch',
            'address' => 'Payments Street',
            'is_active' => true,
        ]);

        $member = Member::query()->create([
            'branch_id' => $branch->id,
            'member_number' => 'MBR-20260318-2001',
            'first_name' => 'Carlos',
            'last_name' => 'Reyes',
            'is_active' => true,
        ]);

        $loanType = LoanType::query()->create([
            'name' => 'Appliance Loan',
            'interest_rate' => 4,
            'interest_type' => 'flat',
            'is_active' => true,
        ]);

        $loan = Loan::query()->create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'user_id' => User::query()->firstOrFail()->id,
            'loan_type_id' => $loanType->id,
            'loan_number' => 'LN-20260318-2001',
            'principal_amount' => 1200,
            'interest_rate' => 4,
            'interest_type' => 'flat',
            'term_months' => 2,
            'total_interest' => 96,
            'total_payable' => 1296,
            'monthly_payment' => 648,
            'amount_paid' => 0,
            'outstanding_balance' => 1296,
            'status' => 'active',
            'release_date' => today(),
            'due_date' => today()->addMonths(2),
        ]);

        app(LoanService::class)->generateAmortizationSchedule($loan);

        return $loan;
    });

    actingAs($user);

    $this->post(tenantUrl('payments', '/loan-payments'), [
        'loan_id' => $loan->id,
        'amount' => 648,
        'payment_date' => today()->toDateString(),
        'period_covered' => 'March 2026',
    ])->assertRedirect('/loans/'.$loan->id);

    $tenant->run(static function () use ($loan): void {
        $loan = Loan::query()->findOrFail($loan->id);
        $firstSchedule = LoanSchedule::query()->where('loan_id', $loan->id)->orderBy('period_number')->firstOrFail();

        expect((float) $loan->amount_paid)->toBe(648.0);
        expect((float) $loan->outstanding_balance)->toBe(648.0);
        expect($firstSchedule->status)->toBe('paid');
    });
});

test('loan payment pages render tenant collection views', function (): void {
    $tenant = provisionTenant('payment-pages');
    $user = createTenantUser($tenant, 'cashier');

    $loanId = $tenant->run(function (): int {
        $branch = Branch::query()->create([
            'name' => 'Collections Branch',
            'address' => 'Collections Street',
            'is_active' => true,
        ]);

        $member = Member::query()->create([
            'branch_id' => $branch->id,
            'member_number' => 'MBR-20260318-4003',
            'first_name' => 'Ramon',
            'last_name' => 'Cruz',
            'is_active' => true,
        ]);

        $loanType = LoanType::query()->create([
            'name' => 'Quick Cash',
            'interest_rate' => 6,
            'interest_type' => 'flat',
            'is_active' => true,
        ]);

        $loan = Loan::query()->create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'user_id' => User::query()->firstOrFail()->id,
            'loan_type_id' => $loanType->id,
            'loan_number' => 'LN-20260318-4003',
            'principal_amount' => 2000,
            'interest_rate' => 6,
            'interest_type' => 'flat',
            'term_months' => 4,
            'total_interest' => 480,
            'total_payable' => 2480,
            'monthly_payment' => 620,
            'amount_paid' => 620,
            'outstanding_balance' => 1860,
            'status' => 'active',
            'release_date' => today(),
            'due_date' => today()->addMonths(4),
        ]);

        LoanPayment::query()->create([
            'loan_id' => $loan->id,
            'user_id' => User::query()->firstOrFail()->id,
            'amount' => 620,
            'payment_date' => today(),
            'period_covered' => 'March 2026',
        ]);

        return $loan->id;
    });

    actingAs($user);

    $this->get(tenantUrl('payment-pages', '/loan-payments?member_search=Ramon'))
        ->assertOk()
        ->assertSee('Loan Payments')
        ->assertSee('Total Collected in Filtered Period')
        ->assertSee('LN-20260318-4003');

    $this->get(tenantUrl('payment-pages', '/loan-payments/create?loan='.$loanId))
        ->assertOk()
        ->assertSee('Loan Summary')
        ->assertSee('Outstanding Balance')
        ->assertSee('Record Payment');
});

test('report page renders computed tenant report data', function (): void {
    $tenant = provisionTenant('reports');
    $user = createTenantUser($tenant, 'viewer');

    $tenant->run(function (): void {
        $branch = Branch::query()->create([
            'name' => 'Reports Branch',
            'address' => 'Reports Street',
            'is_active' => true,
        ]);

        $member = Member::query()->create([
            'branch_id' => $branch->id,
            'member_number' => 'MBR-20260318-3001',
            'first_name' => 'Liza',
            'last_name' => 'Garcia',
            'is_active' => true,
        ]);

        $loanType = LoanType::query()->create([
            'name' => 'Food Loan',
            'interest_rate' => 3,
            'interest_type' => 'flat',
            'is_active' => true,
        ]);

        $loan = Loan::query()->create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'user_id' => User::query()->firstOrFail()->id,
            'loan_type_id' => $loanType->id,
            'loan_number' => 'LN-20260318-3001',
            'principal_amount' => 500,
            'interest_rate' => 3,
            'interest_type' => 'flat',
            'term_months' => 1,
            'total_interest' => 15,
            'total_payable' => 515,
            'monthly_payment' => 515,
            'amount_paid' => 515,
            'outstanding_balance' => 0,
            'status' => 'fully_paid',
            'release_date' => today(),
            'due_date' => today()->addMonth(),
        ]);

        LoanPayment::query()->create([
            'loan_id' => $loan->id,
            'user_id' => User::query()->firstOrFail()->id,
            'amount' => 515,
            'payment_date' => today(),
        ]);

        LoanSchedule::query()->create([
            'loan_id' => $loan->id,
            'period_number' => 1,
            'due_date' => today()->addMonth(),
            'amount_due' => 515,
            'principal_portion' => 500,
            'interest_portion' => 15,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    });

    actingAs($user);

    $this->get(tenantUrl('reports', '/reports'))
        ->assertOk()
        ->assertSee('Food Loan')
        ->assertSee('515.00')
        ->assertSee('15.00')
        ->assertSee('Collections by Month')
        ->assertSee('Top 10 Borrowers by Outstanding Balance')
        ->assertSee('Fully Paid Loans');
});
