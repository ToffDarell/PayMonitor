<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'interest_rate',
        'interest_type',
        'max_term_months',
        'min_amount',
        'max_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'interest_rate' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function computeTotalInterest(float $principal, int $termMonths): float
    {
        if ($principal <= 0 || $termMonths <= 0) {
            return 0.0;
        }

        $rate = ((float) $this->interest_rate) / 100;

        if ($rate <= 0) {
            return 0.0;
        }

        if ($this->interest_type === 'diminishing') {
            $growthFactor = (1 + $rate) ** $termMonths;
            $monthlyPayment = $principal * (($rate * $growthFactor) / ($growthFactor - 1));

            return round(($monthlyPayment * $termMonths) - $principal, 2);
        }

        return round($principal * $rate * $termMonths, 2);
    }
}
