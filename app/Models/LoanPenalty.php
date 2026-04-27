<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPenalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'user_id',
        'penalty_type',
        'penalty_rate',
        'penalty_amount',
        'reason',
        'applied_at',
    ];

    protected $casts = [
        'penalty_rate' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
