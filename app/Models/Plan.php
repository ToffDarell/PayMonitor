<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const DEFAULT_DESCRIPTION = "Secure cooperative portal access\nLoan and member management\nCentralized reporting tools";

    protected $fillable = [
        'name',
        'price',
        'max_branches',
        'max_users',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public static function defaultDescription(): string
    {
        return self::DEFAULT_DESCRIPTION;
    }
}
