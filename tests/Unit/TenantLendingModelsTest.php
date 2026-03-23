<?php

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Traits\HasRoles;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Artisan::call('migrate:fresh', [
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ]);
});

test('tenant user model uses the spatie roles trait', function () {
    expect(class_uses_recursive(User::class))->toContain(HasRoles::class);
});

test('member accessors return the expected lending summaries', function () {
    $branch = Branch::query()->create([
        'name' => 'Main Branch',
        'address' => 'Central',
        'is_active' => true,
    ]);

    $member = Member::query()->create([
        'branch_id' => $branch->id,
        'member_number' => 'MEM-0001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'middle_name' => 'Santos',
        'is_active' => true,
    ]);

    Loan::query()->create([
        'member_id' => $member->id,
        'branch_id' => $branch->id,
        'user_id' => User::query()->create([
            'name' => 'Officer One',
            'email' => 'officer@example.test',
            'password' => 'secret',
            'branch_id' => $branch->id,
        ])->id,
        'loan_type_id' => LoanType::query()->create([
            'name' => 'Money Loan',
            'interest_rate' => 5,
            'interest_type' => 'flat',
            'is_active' => true,
        ])->id,
        'loan_number' => 'LN-0001',
        'principal_amount' => 1000,
        'interest_rate' => 5,
        'interest_type' => 'flat',
        'term_months' => 2,
        'total_interest' => 100,
        'total_payable' => 1100,
        'monthly_payment' => 550,
        'amount_paid' => 100,
        'outstanding_balance' => 1000,
        'status' => 'active',
    ]);

    expect($member->full_name)->toBe('Dela Cruz, Juan Santos');
    expect($member->active_loans_count)->toBe(1);
    expect($member->total_outstanding)->toBe(1000.0);
});

test('loan type computes flat and diminishing interest totals', function () {
    $flatLoanType = new LoanType([
        'interest_rate' => 5,
        'interest_type' => 'flat',
    ]);

    $diminishingLoanType = new LoanType([
        'interest_rate' => 5,
        'interest_type' => 'diminishing',
    ]);

    expect($flatLoanType->computeTotalInterest(1000, 2))->toBe(100.0);
    expect($diminishingLoanType->computeTotalInterest(1000, 2))->toBeGreaterThan(0.0);
});

test('loan compute and fill sets the financial totals', function () {
    $loan = new Loan([
        'principal_amount' => 1000,
        'interest_rate' => 5,
        'interest_type' => 'flat',
        'term_months' => 2,
        'amount_paid' => 100,
    ]);

    $loan->computeAndFill();

    expect((float) $loan->total_interest)->toBe(100.0);
    expect((float) $loan->total_payable)->toBe(1100.0);
    expect((float) $loan->monthly_payment)->toBe(550.0);
    expect((float) $loan->outstanding_balance)->toBe(1000.0);
    expect($loan->amount_remaining)->toBe(1000.0);
});

test('loan payments update the parent loan totals and status', function () {
    $branch = Branch::query()->create([
        'name' => 'Main Branch',
        'address' => 'Central',
        'is_active' => true,
    ]);

    $cashier = User::query()->create([
        'name' => 'Cashier',
        'email' => 'cashier@example.test',
        'password' => 'secret',
        'branch_id' => $branch->id,
    ]);

    $loanOfficer = User::query()->create([
        'name' => 'Loan Officer',
        'email' => 'officer2@example.test',
        'password' => 'secret',
        'branch_id' => $branch->id,
    ]);

    $member = Member::query()->create([
        'branch_id' => $branch->id,
        'member_number' => 'MEM-0002',
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'is_active' => true,
    ]);

    $loanType = LoanType::query()->create([
        'name' => 'Appliance Loan',
        'interest_rate' => 5,
        'interest_type' => 'flat',
        'is_active' => true,
    ]);

    $loan = Loan::query()->create([
        'member_id' => $member->id,
        'branch_id' => $branch->id,
        'user_id' => $loanOfficer->id,
        'loan_type_id' => $loanType->id,
        'loan_number' => 'LN-0002',
        'principal_amount' => 1000,
        'interest_rate' => 5,
        'interest_type' => 'flat',
        'term_months' => 2,
        'total_interest' => 100,
        'total_payable' => 1100,
        'monthly_payment' => 550,
        'amount_paid' => 0,
        'outstanding_balance' => 1100,
        'status' => 'active',
    ]);

    LoanPayment::query()->create([
        'loan_id' => $loan->id,
        'user_id' => $cashier->id,
        'amount' => 1100,
        'payment_date' => now()->toDateString(),
    ]);

    $loan->refresh();

    expect((float) $loan->amount_paid)->toBe(1100.0);
    expect((float) $loan->outstanding_balance)->toBe(0.0);
    expect($loan->status)->toBe('fully_paid');
});
