<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'branch_id',
        'user_id',
        'loan_type_id',
        'loan_number',
        'principal_amount',
        'interest_rate',
        'interest_type',
        'term_months',
        'total_interest',
        'total_payable',
        'monthly_payment',
        'amount_paid',
        'outstanding_balance',
        'status',
        'release_date',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'total_interest' => 'decimal:2',
            'total_payable' => 'decimal:2',
            'monthly_payment' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'release_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function loanSchedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class);
    }

    public function computeAndFill(): static
    {
        $principal = (float) $this->principal_amount;
        $termMonths = (int) $this->term_months;
        $rate = ((float) $this->interest_rate) / 100;

        if ($principal <= 0 || $termMonths <= 0) {
            $this->total_interest = 0;
            $this->total_payable = 0;
            $this->monthly_payment = 0;
            $this->outstanding_balance = 0;

            return $this;
        }

        if ($this->interest_type === 'diminishing' && $rate > 0) {
            $growthFactor = (1 + $rate) ** $termMonths;
            $monthlyPayment = $principal * (($rate * $growthFactor) / ($growthFactor - 1));
            $totalPayable = $monthlyPayment * $termMonths;
            $totalInterest = $totalPayable - $principal;
        } else {
            $totalInterest = $principal * $rate * $termMonths;
            $totalPayable = $principal + $totalInterest;
            $monthlyPayment = $totalPayable / $termMonths;
        }

        $amountPaid = (float) ($this->amount_paid ?? 0);

        $this->total_interest = round($totalInterest, 2);
        $this->total_payable = round($totalPayable, 2);
        $this->monthly_payment = round($monthlyPayment, 2);
        $this->outstanding_balance = round(max($this->total_payable - $amountPaid, 0), 2);

        return $this;
    }

    public function isOverdue(): bool
    {
        if ($this->status === 'fully_paid' || $this->due_date === null) {
            return false;
        }

        return $this->due_date->lt(today());
    }

    public function getAmountRemainingAttribute(): float
    {
        return (float) $this->outstanding_balance;
    }
}
