<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'period_number',
        'due_date',
        'amount_due',
        'principal_portion',
        'interest_portion',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_due' => 'decimal:2',
            'principal_portion' => 'decimal:2',
            'interest_portion' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
