<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
            $loan = $loanPayment->loan()
                ->with('loanSchedules')
                ->first();

            if ($loan === null) {
                return;
            }

            $amountPaid = (float) $loan->loanPayments()->sum('amount');
            $scheduleBalance = (float) $loan->loanSchedules->sum('balance');
            $outstandingBalance = $scheduleBalance > 0 ? $scheduleBalance : round(max((float) $loan->total_payable - $amountPaid, 0), 2);

            $isSettled = $outstandingBalance <= 0;

            if ($isSettled) {
                $loan->loanSchedules()
                    ->whereIn('status', ['pending', 'partially_paid'])
                    ->where('balance', '>', 0)
                    ->update([
                        'amount_paid' => DB::raw('amount_due'),
                        'balance' => 0,
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
            }

            $loan->forceFill([
                'amount_paid' => round($amountPaid, 2),
                'outstanding_balance' => $outstandingBalance,
                'status' => $isSettled ? 'fully_paid' : $loan->status,
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
