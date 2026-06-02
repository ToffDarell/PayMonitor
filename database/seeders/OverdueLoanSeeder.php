<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanPenalty;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoanService;
use App\Support\TenantPermissions;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OverdueLoanSeeder extends Seeder
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
            $this->command->info("Seeding overdue loans for: {$tenant->name}");
            $this->seedOverdueLoansForTenant($tenant);
        }
    }

    private function seedOverdueLoansForTenant(Tenant $tenant): void
    {
        $loanService = $this->loanService;

        $tenant->run(function () use ($loanService): void {
            TenantPermissions::ensureConfigured();

            $branch = Branch::first();
            $loanTypes = LoanType::all();
            $tenantAdmin = User::role('tenant_admin')->first();

            if ($branch === null || $loanTypes->isEmpty() || $tenantAdmin === null) {
                return;
            }

            $firstLoanType = $loanTypes->first();
            $secondLoanType = $loanTypes->skip(1)->first() ?? $firstLoanType;

            $scenarios = [
                [
                    'key' => 'recently_overdue',
                    'member' => [
                        'member_number' => 'MBR-OD-'.now()->format('Ymd').'-001',
                        'first_name' => 'Juan',
                        'last_name' => 'Overdue',
                        'phone' => '09170000001',
                        'occupation' => 'Fisherman',
                        'joined_at' => now()->subMonths(6)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $firstLoanType->name,
                        'principal_amount' => 5000,
                        'term_months' => 3,
                        'release_date' => now()->subMonths(4)->toDateString(),
                        'payment_amount' => 0,
                    ],
                ],
                [
                    'key' => 'partial_payment',
                    'member' => [
                        'member_number' => 'MBR-OD-'.now()->format('Ymd').'-002',
                        'first_name' => 'Maria',
                        'last_name' => 'PartiallyPaid',
                        'phone' => '09170000002',
                        'occupation' => 'Sari-sari Store Owner',
                        'joined_at' => now()->subMonths(12)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $secondLoanType->name,
                        'principal_amount' => 10000,
                        'term_months' => 6,
                        'release_date' => now()->subMonths(7)->toDateString(),
                        'payment_amount' => null,
                    ],
                ],
                [
                    'key' => 'delinquent',
                    'member' => [
                        'member_number' => 'MBR-OD-'.now()->format('Ymd').'-003',
                        'first_name' => 'Pedro',
                        'last_name' => 'Delinquent',
                        'phone' => '09170000003',
                        'occupation' => 'Farmer',
                        'joined_at' => now()->subYears(1)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $firstLoanType->name,
                        'principal_amount' => 20000,
                        'term_months' => 12,
                        'release_date' => now()->subMonths(14)->toDateString(),
                        'payment_amount' => null,
                    ],
                ],
                [
                    'key' => 'demand_letter',
                    'member' => [
                        'member_number' => 'MBR-OD-'.now()->format('Ymd').'-004',
                        'first_name' => 'Ana',
                        'last_name' => 'Demanded',
                        'phone' => '09170000004',
                        'occupation' => 'Teacher',
                        'joined_at' => now()->subMonths(8)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $secondLoanType->name,
                        'principal_amount' => 8000,
                        'term_months' => 4,
                        'release_date' => now()->subMonths(6)->toDateString(),
                        'payment_amount' => 0,
                    ],
                ],
                [
                    'key' => 'written_off',
                    'member' => [
                        'member_number' => 'MBR-OD-'.now()->format('Ymd').'-005',
                        'first_name' => 'Rizal',
                        'last_name' => 'WrittenOff',
                        'phone' => '09170000005',
                        'occupation' => 'Driver',
                        'joined_at' => now()->subYears(2)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $firstLoanType->name,
                        'principal_amount' => 15000,
                        'term_months' => 6,
                        'release_date' => now()->subMonths(12)->toDateString(),
                        'payment_amount' => 0,
                    ],
                ],
                [
                    'key' => 'restructured',
                    'member' => [
                        'member_number' => 'MBR-OD-'.now()->format('Ymd').'-006',
                        'first_name' => 'Luz',
                        'last_name' => 'Restructured',
                        'phone' => '09170000006',
                        'occupation' => 'Vendor',
                        'joined_at' => now()->subMonths(18)->toDateString(),
                    ],
                    'loan' => [
                        'loan_type_name' => $secondLoanType->name,
                        'principal_amount' => 12000,
                        'term_months' => 12,
                        'release_date' => now()->subMonths(9)->toDateString(),
                        'payment_amount' => 0,
                    ],
                ],
            ];

            $existingIds = Member::query()
                ->where('member_number', 'like', 'MBR-OD-%')
                ->pluck('id');

            if ($existingIds->isNotEmpty()) {
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
                    'status' => match ($scenario['key']) {
                        'written_off' => 'overdue',
                        'restructured' => 'overdue',
                        default => 'overdue',
                    },
                    'release_date' => $releaseDate->toDateString(),
                    'due_date' => match ($scenario['key']) {
                        'recently_overdue' => now()->subDays(7)->toDateString(),
                        'partial_payment' => now()->subDays(30)->toDateString(),
                        'delinquent' => now()->subDays(65)->toDateString(),
                        'demand_letter' => now()->subDays(45)->toDateString(),
                        'written_off' => now()->subDays(180)->toDateString(),
                        'restructured' => now()->subDays(90)->toDateString(),
                        default => now()->subDays(7)->toDateString(),
                    },
                    'notes' => match ($scenario['key']) {
                        'recently_overdue' => 'Seeded overdue loan — recently past due.',
                        'partial_payment' => 'Seeded overdue loan — only first payment made.',
                        'delinquent' => 'Seeded overdue loan — classified as delinquent with penalties.',
                        'demand_letter' => 'Seeded overdue loan — demand letter sent.',
                        'written_off' => 'Seeded overdue loan — eventually written off.',
                        'restructured' => 'Seeded overdue loan — restructured after prolonged delinquency.',
                        default => 'Seeded overdue loan.',
                    },
                ]);

                $loanService->generateAmortizationSchedule($loan);

                $paymentAmount = $loanData['payment_amount'];

                if ($scenario['key'] === 'partial_payment') {
                    $paymentAmount = $computedLoan['monthly_payment'];
                }

                if (($paymentAmount !== null && $paymentAmount > 0) || $scenario['key'] === 'partial_payment') {
                    LoanPayment::query()->create([
                        'loan_id' => $loan->id,
                        'user_id' => $tenantAdmin->id,
                        'amount' => $paymentAmount,
                        'payment_date' => $releaseDate->copy()->addMonth()->toDateString(),
                        'period_covered' => $releaseDate->copy()->addMonth()->format('F Y'),
                        'notes' => match ($scenario['key']) {
                            'partial_payment' => 'Only payment collected before account became overdue.',
                            default => 'Initial collection entry.',
                        },
                    ]);
                }

                $loan->refresh();

                match ($scenario['key']) {
                    'delinquent' => $this->applyDelinquentState($loan, $tenantAdmin),
                    'demand_letter' => $this->applyDemandLetterSent($loan),
                    'written_off' => $this->applyWrittenOff($loan, $tenantAdmin),
                    'restructured' => $this->applyRestructured($loan, $loanService, $tenantAdmin),
                    default => $loan->forceFill(['status' => 'overdue'])->save(),
                };
            }
        });
    }

    private function applyDelinquentState(Loan $loan, User $user): void
    {
        $loan->forceFill([
            'is_delinquent' => true,
            'delinquent_at' => now()->subDays(30),
            'status' => 'overdue',
        ])->save();

        $loan->applyPenalty([
            'user_id' => $user->id,
            'penalty_type' => 'percentage',
            'penalty_rate' => 5.00,
            'penalty_amount' => round((float) $loan->outstanding_balance * 0.05, 2),
            'reason' => 'Auto-seeded penalty for delinquent loan testing.',
            'applied_at' => now()->subDays(30),
        ]);
    }

    private function applyDemandLetterSent(Loan $loan): void
    {
        $loan->forceFill([
            'demand_letter_sent_at' => now()->subDays(15),
            'status' => 'overdue',
        ])->save();
    }

    private function applyWrittenOff(Loan $loan, User $user): void
    {
        $loan->forceFill([
            'status' => 'written_off',
            'written_off_at' => now()->subDays(30),
            'is_delinquent' => true,
            'delinquent_at' => now()->subDays(120),
            'demand_letter_sent_at' => now()->subDays(90),
            'outstanding_balance' => 0,
        ])->save();

        $loan->loanSchedules()->whereNull('paid_at')->delete();
    }

    private function applyRestructured(Loan $loan, LoanService $loanService, User $user): void
    {
        $restructuredAmount = round((float) $loan->outstanding_balance, 2);
        $newTermMonths = 12;
        $newMonthlyPayment = round($restructuredAmount / $newTermMonths, 2);
        $newDueDate = now()->addMonthsNoOverflow($newTermMonths);
        $newTotalPayable = round((float) ($loan->amount_paid ?? 0) + $restructuredAmount, 2);

        $loan->loanSchedules()->whereNull('paid_at')->delete();

        $loan->forceFill([
            'term_months' => $newTermMonths,
            'total_interest' => 0,
            'total_payable' => $newTotalPayable,
            'monthly_payment' => $newMonthlyPayment,
            'due_date' => $newDueDate->toDateString(),
            'status' => 'restructured',
            'restructured_at' => now()->subDays(14),
            'outstanding_balance' => $restructuredAmount,
            'notes' => $loan->notes.PHP_EOL.'Seeded restructure after overdue.',
        ])->save();

        $loanService->generateAmortizationSchedule($loan->fresh());
    }
}
