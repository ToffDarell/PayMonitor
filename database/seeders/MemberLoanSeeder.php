<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoanService;
use App\Support\TenantPermissions;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MemberLoanSeeder extends Seeder
{
    public function __construct(
        private LoanService $loanService,
    ) {}

    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Run DatabaseSeeder first.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->command->info("Seeding members and loans for: {$tenant->name}");
            $this->seedForTenant($tenant);
        }
    }

    private function seedForTenant(Tenant $tenant): void
    {
        $loanService = $this->loanService;

        $tenant->run(function () use ($loanService): void {
            TenantPermissions::ensureConfigured();

            $branch = Branch::first();
            $loanTypes = LoanType::all();
            $tenantAdmin = User::role('tenant_admin')->first();

            if ($branch === null || $loanTypes->isEmpty() || $tenantAdmin === null) {
                $this->command->warn('Skipping tenant — missing branch, loan types, or admin.');
                return;
            }

            $flatType = $loanTypes->firstWhere('interest_type', 'flat') ?? $loanTypes->first();
            $diminishingType = $loanTypes->firstWhere('interest_type', 'diminishing') ?? $loanTypes->first();

            $scenarios = [
                [
                    'key' => 'active_new',
                    'member' => [
                        'member_number' => 'MBR-DEMO-'.now()->format('Ymd').'-001',
                        'first_name' => 'Miguel',
                        'last_name' => 'Dimatibag',
                        'phone' => '09170001001',
                        'occupation' => 'Rice Farmer',
                        'joined_at' => now()->subMonths(3)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $flatType->name,
                        'principal_amount' => 5000,
                        'term_months' => 3,
                        'release_date' => now()->subMonth()->toDateString(),
                        'status' => 'active',
                        'due_date' => now()->addMonths(2)->toDateString(),
                        'is_overdue' => false,
                        'payment_amount' => 0,
                        'payment_date' => null,
                    ],
                ],
                [
                    'key' => 'active_midterm',
                    'member' => [
                        'member_number' => 'MBR-DEMO-'.now()->format('Ymd').'-002',
                        'first_name' => 'Elena',
                        'last_name' => 'Roxas',
                        'phone' => '09170001002',
                        'occupation' => 'Sari-sari Store Owner',
                        'joined_at' => now()->subYear()->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $flatType->name,
                        'principal_amount' => 10000,
                        'term_months' => 6,
                        'release_date' => now()->subMonths(3)->toDateString(),
                        'status' => 'active',
                        'due_date' => now()->addMonths(3)->toDateString(),
                        'is_overdue' => false,
                        'payment_amount' => 0,
                        'payment_date' => null,
                    ],
                ],
                [
                    'key' => 'fully_paid',
                    'member' => [
                        'member_number' => 'MBR-DEMO-'.now()->format('Ymd').'-003',
                        'first_name' => 'Ricardo',
                        'last_name' => 'Dimalanta',
                        'phone' => '09170001003',
                        'occupation' => 'Tricycle Driver',
                        'joined_at' => now()->subYears(2)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $flatType->name,
                        'principal_amount' => 3000,
                        'term_months' => 2,
                        'release_date' => now()->subMonths(4)->toDateString(),
                        'status' => 'fully_paid',
                        'due_date' => now()->subMonths(2)->toDateString(),
                        'is_overdue' => false,
                        'payment_amount' => null,
                        'payment_date' => null,
                    ],
                ],
                [
                    'key' => 'overdue_no_payment',
                    'member' => [
                        'member_number' => 'MBR-DEMO-'.now()->format('Ymd').'-004',
                        'first_name' => 'Lydia',
                        'last_name' => 'Samson',
                        'phone' => '09170001004',
                        'occupation' => 'Market Vendor',
                        'joined_at' => now()->subMonths(9)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $flatType->name,
                        'principal_amount' => 7000,
                        'term_months' => 3,
                        'release_date' => now()->subMonths(4)->toDateString(),
                        'status' => 'overdue',
                        'due_date' => now()->subDays(10)->toDateString(),
                        'is_overdue' => true,
                        'payment_amount' => 0,
                        'payment_date' => null,
                    ],
                ],
                [
                    'key' => 'overdue_partial',
                    'member' => [
                        'member_number' => 'MBR-DEMO-'.now()->format('Ymd').'-005',
                        'first_name' => 'Nestor',
                        'last_name' => 'Villanueva',
                        'phone' => '09170001005',
                        'occupation' => 'Construction Worker',
                        'joined_at' => now()->subMonths(15)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $diminishingType->name,
                        'principal_amount' => 12000,
                        'term_months' => 6,
                        'release_date' => now()->subMonths(7)->toDateString(),
                        'status' => 'overdue',
                        'due_date' => now()->subDays(25)->toDateString(),
                        'is_overdue' => true,
                        'payment_amount' => null,
                        'payment_date' => null,
                    ],
                ],
                [
                    'key' => 'overdue_long',
                    'member' => [
                        'member_number' => 'MBR-DEMO-'.now()->format('Ymd').'-006',
                        'first_name' => 'Alicia',
                        'last_name' => 'Magsaysay',
                        'phone' => '09170001006',
                        'occupation' => 'Public School Teacher',
                        'joined_at' => now()->subYears(3)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $flatType->name,
                        'principal_amount' => 15000,
                        'term_months' => 6,
                        'release_date' => now()->subMonths(8)->toDateString(),
                        'status' => 'overdue',
                        'due_date' => now()->subMonths(2)->toDateString(),
                        'is_overdue' => true,
                        'payment_amount' => 0,
                        'payment_date' => null,
                    ],
                ],
                [
                    'key' => 'active_recent',
                    'member' => [
                        'member_number' => 'MBR-DEMO-'.now()->format('Ymd').'-007',
                        'first_name' => 'Gregorio',
                        'last_name' => 'Macapagal',
                        'phone' => '09170001007',
                        'occupation' => 'Fishing Boat Operator',
                        'joined_at' => now()->subWeeks(2)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $diminishingType->name,
                        'principal_amount' => 25000,
                        'term_months' => 12,
                        'release_date' => now()->subDays(5)->toDateString(),
                        'status' => 'active',
                        'due_date' => now()->addMonths(12)->toDateString(),
                        'is_overdue' => false,
                        'payment_amount' => 0,
                        'payment_date' => null,
                    ],
                ],
                [
                    'key' => 'delinquent',
                    'member' => [
                        'member_number' => 'MBR-DEMO-'.now()->format('Ymd').'-008',
                        'first_name' => 'Rosario',
                        'last_name' => 'Lopez',
                        'phone' => '09170001008',
                        'occupation' => 'Palay Trader',
                        'joined_at' => now()->subYears(4)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $flatType->name,
                        'principal_amount' => 20000,
                        'term_months' => 6,
                        'release_date' => now()->subMonths(9)->toDateString(),
                        'status' => 'overdue',
                        'due_date' => now()->subMonths(3)->toDateString(),
                        'is_overdue' => true,
                        'payment_amount' => 0,
                        'payment_date' => null,
                    ],
                ],
            ];

            $existingIds = Member::query()
                ->where('member_number', 'like', 'MBR-DEMO-%')
                ->pluck('id');

            if ($existingIds->isNotEmpty()) {
                LoanPayment::query()->whereIn('loan_id', Loan::query()->whereIn('member_id', $existingIds)->pluck('id'))->delete();
                Loan::query()->whereIn('member_id', $existingIds)->delete();
                Member::query()->whereIn('id', $existingIds)->delete();
            }

            foreach ($scenarios as $scenario) {
                $member = Member::query()->create([
                    'branch_id' => $branch->id,
                    'birthdate' => null,
                    'gender' => null,
                    'civil_status' => null,
                    'address' => $branch->name,
                    'email' => null,
                    'is_active' => true,
                    ...$scenario['member'],
                ]);

                $loanType = LoanType::query()
                    ->where('name', $scenario['loan']['loan_type_name'])
                    ->first();

                if ($loanType === null) {
                    continue;
                }

                $loanData = $scenario['loan'];
                $releaseDate = Carbon::parse($loanData['release_date']);

                $computedLoan = $loanService->computeLoan([
                    'principal_amount' => $loanData['principal_amount'],
                    'interest_rate' => $loanType->interest_rate,
                    'interest_type' => $loanType->interest_type,
                    'term_months' => $loanData['term_months'],
                ]);

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
                    'due_date' => $loanData['due_date'],
                    'notes' => match ($scenario['key']) {
                        'active_new' => 'Seeded demo — newly released active loan.',
                        'active_midterm' => 'Seeded demo — active loan, mid-term, on track.',
                        'fully_paid' => 'Seeded demo — fully paid and closed.',
                        'overdue_no_payment' => 'Seeded demo — overdue, no payments made.',
                        'overdue_partial' => 'Seeded demo — overdue, only partial payment made.',
                        'overdue_long' => 'Seeded demo — overdue, 2+ months past due.',
                        'active_recent' => 'Seeded demo — just released this week.',
                        'delinquent' => 'Seeded demo — delinquent, overdue 3+ months.',
                        default => 'Seeded demo loan.',
                    },
                ]);

                $loanService->generateAmortizationSchedule($loan);

                if ($scenario['key'] === 'fully_paid') {
                    $schedules = $loan->loanSchedules()->orderBy('period_number')->get();
                    $totalPaid = 0;

                    foreach ($schedules as $schedule) {
                        $schedule->forceFill([
                            'amount_paid' => $schedule->amount_due,
                            'balance' => 0,
                            'status' => 'paid',
                            'paid_at' => $releaseDate->copy()->addMonthsNoOverflow($schedule->period_number)->addDays(2),
                        ])->save();
                        $totalPaid += (float) $schedule->amount_due;
                    }

                    $loan->forceFill([
                        'amount_paid' => round($totalPaid, 2),
                        'outstanding_balance' => 0,
                        'status' => 'fully_paid',
                    ])->save();

                    continue;
                }

                $paymentAmount = $loanData['payment_amount'];
                $paymentDate = $loanData['payment_date'];

                if ($scenario['key'] === 'overdue_partial') {
                    $paymentAmount = $computedLoan['monthly_payment'];
                    $paymentDate = $releaseDate->copy()->addMonth()->toDateString();
                }

                if ($paymentAmount !== null && $paymentAmount > 0 && $paymentDate !== null) {
                    LoanPayment::query()->create([
                        'loan_id' => $loan->id,
                        'user_id' => $tenantAdmin->id,
                        'amount' => $paymentAmount,
                        'payment_date' => $paymentDate,
                        'period_covered' => Carbon::parse($paymentDate)->format('F Y'),
                        'notes' => match ($scenario['key']) {
                            'overdue_partial' => 'Only payment made before account went overdue.',
                            default => 'Demo collection entry.',
                        },
                    ]);
                }

                if ($scenario['key'] === 'overdue_partial') {
                    $loan->loanSchedules()
                        ->where('period_number', 1)
                        ->update([
                            'amount_paid' => $paymentAmount,
                            'status' => 'partially_paid',
                        ]);
                }

                if ($scenario['key'] === 'delinquent') {
                    $loan->forceFill([
                        'is_delinquent' => true,
                        'delinquent_at' => now()->subDays(60),
                    ])->save();

                    $loan->applyPenalty([
                        'user_id' => $tenantAdmin->id,
                        'penalty_type' => 'percentage',
                        'penalty_rate' => 5.00,
                        'penalty_amount' => round((float) $loan->outstanding_balance * 0.05, 2),
                        'reason' => 'Auto-seeded penalty for delinquent loan.',
                        'applied_at' => now()->subDays(30),
                    ]);
                }
            }
        });
    }
}
