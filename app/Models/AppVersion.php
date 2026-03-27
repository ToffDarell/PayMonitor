<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class AppVersion extends Model
{
    use CentralConnection;
    use HasFactory;

    protected $fillable = [
        'version_number',
        'title',
        'changelog',
        'is_active',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'released_at' => 'datetime',
        ];
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(TenantVersionAcknowledgement::class, 'version_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function latestActive(): ?self
    {
        return static::query()
            ->active()
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->first();
    }

    public function getChangelogItemsAttribute(): array
    {
        return collect(preg_split('/\R+/', trim((string) $this->changelog)) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
