<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'user_id',
        'amount',
        'payment_date',
        'period_covered',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (LoanPayment $loanPayment): void {
            $loan = $loanPayment->loan()->first();

            if ($loan === null) {
                return;
            }

            $amountPaid = (float) $loan->loanPayments()->sum('amount');
            $outstandingBalance = round(max((float) $loan->total_payable - $amountPaid, 0), 2);

            $loan->forceFill([
                'amount_paid' => round($amountPaid, 2),
                'outstanding_balance' => $outstandingBalance,
                'status' => $outstandingBalance <= 0 ? 'fully_paid' : $loan->status,
            ])->save();
        });
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
