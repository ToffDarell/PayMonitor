<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Carbon\Carbon;

class LoanService
{
    public function computeLoan(array $data): array
    {
        $principal = (float) $data['principal_amount'];
        $interestRate = (float) $data['interest_rate'];
        $termMonths = (int) $data['term_months'];
        $interestType = (string) $data['interest_type'];

        if ($principal <= 0 || $termMonths <= 0) {
            return [
                'total_interest' => 0.0,
                'total_payable' => 0.0,
                'monthly_payment' => 0.0,
                'outstanding_balance' => 0.0,
            ];
        }

        $ratePerMonth = $interestRate / 100;

        if ($interestType === 'diminishing' && $ratePerMonth > 0) {
            $growthFactor = (1 + $ratePerMonth) ** $termMonths;
            $monthlyPayment = $principal * (($ratePerMonth * $growthFactor) / ($growthFactor - 1));
            $totalPayable = $monthlyPayment * $termMonths;
            $totalInterest = $totalPayable - $principal;
        } else {
            $totalInterest = $principal * $ratePerMonth * $termMonths;
            $totalPayable = $principal + $totalInterest;
            $monthlyPayment = $totalPayable / $termMonths;
        }

        return [
            'total_interest' => round($totalInterest, 2),
            'total_payable' => round($totalPayable, 2),
            'monthly_payment' => round($monthlyPayment, 2),
            'outstanding_balance' => round($totalPayable, 2),
        ];
    }

    public function generateLoanNumber(): string
    {
        do {
            $loanNumber = sprintf('LN-%s-%04d', now()->format('Ymd'), random_int(0, 9999));
        } while (Loan::query()->where('loan_number', $loanNumber)->exists());

        return $loanNumber;
    }

    public function generateAmortizationSchedule(Loan $loan): void
    {
        $loan->loanSchedules()->delete();

        $principal = (float) $loan->principal_amount;
        $termMonths = (int) $loan->term_months;
        $ratePerMonth = ((float) $loan->interest_rate) / 100;
        $releaseDate = Carbon::parse($loan->release_date ?? today());

        if ($principal <= 0 || $termMonths <= 0) {
            return;
        }

        if ($loan->interest_type === 'diminishing') {
            $monthlyPayment = (float) $loan->monthly_payment;
            $remainingPrincipal = $principal;

            for ($period = 1; $period <= $termMonths; $period++) {
                $interestPortion = round($remainingPrincipal * $ratePerMonth, 2);
                $principalPortion = round($monthlyPayment - $interestPortion, 2);

                if ($period === $termMonths) {
                    $principalPortion = round($remainingPrincipal, 2);
                    $amountDue = round($principalPortion + $interestPortion, 2);
                } else {
                    $amountDue = round($monthlyPayment, 2);
                }

                LoanSchedule::create([
                    'loan_id' => $loan->id,
                    'period_number' => $period,
                    'due_date' => $releaseDate->copy()->addMonthsNoOverflow($period)->toDateString(),
                    'amount_due' => $amountDue,
                    'principal_portion' => $principalPortion,
                    'interest_portion' => $interestPortion,
                    'status' => 'pending',
                    'paid_at' => null,
                ]);

                $remainingPrincipal = round(max($remainingPrincipal - $principalPortion, 0), 2);
            }

            return;
        }

        $principalPortion = round($principal / $termMonths, 2);
        $interestPortion = round(((float) $loan->total_interest) / $termMonths, 2);

        for ($period = 1; $period <= $termMonths; $period++) {
            $currentPrincipalPortion = $period === $termMonths
                ? round($principal - ($principalPortion * ($termMonths - 1)), 2)
                : $principalPortion;
            $currentInterestPortion = $period === $termMonths
                ? round((float) $loan->total_interest - ($interestPortion * ($termMonths - 1)), 2)
                : $interestPortion;

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'period_number' => $period,
                'due_date' => $releaseDate->copy()->addMonthsNoOverflow($period)->toDateString(),
                'amount_due' => round($currentPrincipalPortion + $currentInterestPortion, 2),
                'principal_portion' => $currentPrincipalPortion,
                'interest_portion' => $currentInterestPortion,
                'status' => 'pending',
                'paid_at' => null,
            ]);
        }
    }

    public function markOverdueLoans(): void
    {
        Loan::query()
            ->where('status', 'active')
            ->whereDate('due_date', '<', today())
            ->update(['status' => 'overdue']);
    }
}
