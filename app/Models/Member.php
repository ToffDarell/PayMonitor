<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Encrypted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'member_number',
        'first_name',
        'last_name',
        'middle_name',
        'birthdate',
        'gender',
        'civil_status',
        'address',
        'phone',
        'email',
        'occupation',
        'is_active',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'address' => Encrypted::class,
            'occupation' => Encrypted::class,
            'joined_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class)->latest();
    }

    public function getFullNameAttribute(): string
    {
        $middleName = $this->middle_name ? " {$this->middle_name}" : '';

        return "{$this->last_name}, {$this->first_name}{$middleName}";
    }

    public function getActiveLoansCountAttribute(): int
    {
        return $this->loans()
            ->where('status', 'active')
            ->count();
    }

    public function getTotalOutstandingAttribute(): float
    {
        return (float) $this->loans()->sum('outstanding_balance');
    }
}
