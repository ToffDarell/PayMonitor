<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoanService;
use App\Services\TenantService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function __construct(
        private TenantService $tenantService,
        private LoanService $loanService,
    ) {}

    public function run(): void
    {
        Role::findOrCreate('super_admin', 'web');

        $plans = $this->seedPlans();

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'superadmin@paymonitor.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ],
        );
        $superAdmin->syncRoles(['super_admin']);

        $this->deleteExistingTenantDatabase('alpha');
        $this->deleteExistingTenantDatabase('bravo');

        $alphaTenant = $this->tenantService->createTenant([
            'name' => 'Alpha Cooperative',
            'address' => 'Malaybalay City, Bukidnon',
            'domain' => 'alpha',
            'admin_name' => 'Juan Dela Cruz',
            'admin_email' => 'juan@alpha.com',
            'plan_id' => $plans['Basic']->id,
            'subscription_due_at' => now()->addDays(30)->toDateString(),
        ]);

        $this->seedAlphaTenant($alphaTenant);

        $bravoTenant = $this->tenantService->createTenant([
            'name' => 'Bravo Cooperative',
            'address' => 'Valencia City, Bukidnon',
            'domain' => 'bravo',
            'admin_name' => 'Maria Santos',
            'admin_email' => 'maria@bravo.com',
            'plan_id' => $plans['Premium']->id,
            'subscription_due_at' => now()->addDays(45)->toDateString(),
        ]);

        $this->seedBravoTenant($bravoTenant);
    }

    /**
     * @return array<string, Plan>
     */
    private function seedPlans(): array
    {
        $plans = [
            [
                'name' => 'Basic',
                'price' => 499,
                'max_branches' => 2,
                'max_users' => 10,
                'description' => 'Starter plan for small lending cooperatives.',
            ],
            [
                'name' => 'Standard',
                'price' => 999,
                'max_branches' => 5,
                'max_users' => 30,
                'description' => 'Growing cooperative plan with wider staffing limits.',
            ],
            [
                'name' => 'Premium',
                'price' => 1999,
                'max_branches' => 0,
                'max_users' => 0,
                'description' => 'Unlimited plan for established lending cooperatives.',
            ],
        ];

        $seededPlans = [];

        foreach ($plans as $planData) {
            $plan = Plan::query()->updateOrCreate(
                ['name' => $planData['name']],
                $planData,
            );

            $seededPlans[$plan->name] = $plan;
        }

        return $seededPlans;
    }

    private function deleteExistingTenantDatabase(string $tenantId): void
    {
        $tenant = new Tenant([
            'id' => $tenantId,
        ]);

        $databaseManager = $tenant->database()->manager();
        $databaseName = $tenant->database()->getName();

        if ($databaseManager->databaseExists($databaseName)) {
            $databaseManager->deleteDatabase($tenant);
        }
    }

    private function seedAlphaTenant(Tenant $tenant): void
    {
        $this->seedTenantDataset(
            tenant: $tenant,
            branchName: 'Main Branch - Malaybalay',
            loanTypes: [
                [
                    'name' => 'Money Loan',
                    'description' => 'Short-term cash assistance loan.',
                    'interest_rate' => 5,
                    'interest_type' => 'flat',
                    'max_term_months' => 12,
                    'min_amount' => 1000,
                    'max_amount' => 50000,
                    'is_active' => true,
                ],
                [
                    'name' => 'Appliance Loan',
                    'description' => 'Loan product for appliance purchases.',
                    'interest_rate' => 3,
                    'interest_type' => 'flat',
                    'max_term_months' => 24,
                    'min_amount' => 3000,
                    'max_amount' => 100000,
                    'is_active' => true,
                ],
            ],
            members: [
                [
                    'member_number' => 'MBR-20260101-0001',
                    'first_name' => 'Pedro',
                    'last_name' => 'Reyes',
                    'middle_name' => 'Lopez',
                    'phone' => '09171234567',
                    'email' => 'pedro.reyes@alpha.com',
                    'occupation' => 'Farmer',
                    'joined_at' => '2026-01-01',
                ],
                [
                    'member_number' => 'MBR-20260101-0002',
                    'first_name' => 'Ana',
                    'last_name' => 'Cruz',
                    'middle_name' => 'Santos',
                    'phone' => '09181234567',
                    'email' => 'ana.cruz@alpha.com',
                    'occupation' => 'Teacher',
                    'joined_at' => '2026-01-01',
                ],
            ],
            loanData: [
                'member_number' => 'MBR-20260101-0001',
                'loan_type_name' => 'Money Loan',
                'principal_amount' => 10000,
                'term_months' => 6,
                'release_date' => today()->toDateString(),
                'notes' => 'Seeded demo loan for Alpha Cooperative.',
                'payment_amount' => 1833.33,
                'payment_date' => today()->toDateString(),
                'period_covered' => now()->format('F Y'),
                'payment_notes' => 'Initial demo collection entry.',
                'status' => 'active',
            ],
        );
    }

    private function seedBravoTenant(Tenant $tenant): void
    {
        $this->seedTenantDataset(
            tenant: $tenant,
            branchName: 'Main Branch - Valencia',
            loanTypes: [
                [
                    'name' => 'Business Loan',
                    'description' => 'Working capital loan for cooperative members.',
                    'interest_rate' => 4,
                    'interest_type' => 'flat',
                    'max_term_months' => 12,
                    'min_amount' => 5000,
                    'max_amount' => 75000,
                    'is_active' => true,
                ],
                [
                    'name' => 'Salary Loan',
                    'description' => 'Short-term salary-backed loan facility.',
                    'interest_rate' => 3.5,
                    'interest_type' => 'flat',
                    'max_term_months' => 18,
                    'min_amount' => 3000,
                    'max_amount' => 40000,
                    'is_active' => true,
                ],
            ],
            members: [
                [
                    'member_number' => 'MBR-20260102-0001',
                    'first_name' => 'Roberto',
                    'last_name' => 'Garcia',
                    'middle_name' => 'Diaz',
                    'phone' => '09192223344',
                    'email' => 'roberto.garcia@bravo.com',
                    'occupation' => 'Entrepreneur',
                    'joined_at' => '2026-01-02',
                ],
                [
                    'member_number' => 'MBR-20260102-0002',
                    'first_name' => 'Lorna',
                    'last_name' => 'Fernandez',
                    'middle_name' => 'Cruz',
                    'phone' => '09195556677',
                    'email' => 'lorna.fernandez@bravo.com',
                    'occupation' => 'Vendor',
                    'joined_at' => '2026-01-02',
                ],
            ],
            loanData: [
                'member_number' => 'MBR-20260102-0001',
                'loan_type_name' => 'Business Loan',
                'principal_amount' => 15000,
                'term_months' => 6,
                'release_date' => today()->subMonths(7)->toDateString(),
                'notes' => 'Seeded overdue loan for Bravo Cooperative.',
                'payment_amount' => 3100,
                'payment_date' => today()->subMonths(6)->toDateString(),
                'period_covered' => today()->subMonths(6)->format('F Y'),
                'payment_notes' => 'First installment collected before account became overdue.',
                'status' => 'overdue',
                'due_date' => today()->subDays(15)->toDateString(),
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $loanTypes
     * @param  array<int, array<string, mixed>>  $members
     * @param  array<string, mixed>  $loanData
     */
    private function seedTenantDataset(
        Tenant $tenant,
        string $branchName,
        array $loanTypes,
        array $members,
        array $loanData,
    ): void {
        $loanService = $this->loanService;

        $tenant->run(static function () use ($branchName, $loanData, $loanService, $loanTypes, $members): void {
            foreach (['tenant_admin', 'branch_manager', 'loan_officer', 'cashier', 'viewer'] as $role) {
                Role::findOrCreate($role, 'web');
            }

            $branch = Branch::query()->create([
                'name' => $branchName,
                'address' => $branchName,
                'is_active' => true,
            ]);

            foreach ($loanTypes as $loanTypeData) {
                LoanType::query()->create($loanTypeData);
            }

            foreach ($members as $memberData) {
                Member::query()->create([
                    'branch_id' => $branch->id,
                    'birthdate' => null,
                    'gender' => null,
                    'civil_status' => null,
                    'address' => $branchName,
                    'is_active' => true,
                    ...$memberData,
                ]);
            }

            $tenantAdmin = User::role('tenant_admin')->firstOrFail();
            $tenantAdmin->branch_id = $branch->id;
            $tenantAdmin->save();

            $member = Member::query()
                ->where('member_number', $loanData['member_number'])
                ->firstOrFail();
            $loanType = LoanType::query()
                ->where('name', $loanData['loan_type_name'])
                ->firstOrFail();

            $computedLoan = $loanService->computeLoan([
                'principal_amount' => $loanData['principal_amount'],
                'interest_rate' => $loanType->interest_rate,
                'interest_type' => $loanType->interest_type,
                'term_months' => $loanData['term_months'],
            ]);

            $releaseDate = \Illuminate\Support\Carbon::parse($loanData['release_date']);
            $dueDate = $loanData['due_date'] ?? $releaseDate->copy()->addMonthsNoOverflow((int) $loanData['term_months'])->toDateString();

            $loan = Loan::query()->create([
                'member_id' => $member->id,
                'branch_id' => $branch->id,
                'user_id' => $tenantAdmin->id,
                'loan_type_id' => $loanType->id,
                'loan_number' => $loanService->generateLoanNumber(),
                'principal_amount' => $loanData['principal_amount'],
                'interest_rate' => $loanType->interest_rate,
                'interest_type' => $loanType->interest_type,
                'term_months' => $loanData['term_months'],
                'total_interest' => $computedLoan['total_interest'],
                'total_payable' => $computedLoan['total_payable'],
                'monthly_payment' => $computedLoan['monthly_payment'],
                'amount_paid' => 0,
                'outstanding_balance' => $computedLoan['outstanding_balance'],
                'status' => $loanData['status'],
                'release_date' => $releaseDate->toDateString(),
                'due_date' => $dueDate,
                'notes' => $loanData['notes'],
            ]);

            $loanService->generateAmortizationSchedule($loan);

            LoanPayment::query()->create([
                'loan_id' => $loan->id,
                'user_id' => $tenantAdmin->id,
                'amount' => $loanData['payment_amount'],
                'payment_date' => $loanData['payment_date'],
                'period_covered' => $loanData['period_covered'],
                'notes' => $loanData['payment_notes'],
            ]);

            if ($loanData['status'] === 'overdue') {
                $loan->refresh();
                $loan->status = 'overdue';
                $loan->save();
            }
        });
    }
}
