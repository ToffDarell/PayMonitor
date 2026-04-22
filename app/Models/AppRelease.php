<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class AppRelease extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tag',
        'title',
        'changelog',
        'release_url',
        'published_at',
        'is_stable',
        'is_required',
        'synced_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'synced_at' => 'datetime',
        'is_stable' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function tenantUpdates(): HasMany
    {
        return $this->hasMany(TenantUpdate::class);
    }

    public function scopeStable($query)
    {
        return $query->where('is_stable', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    public function getVersionAttribute(): string
    {
        return ltrim($this->tag, 'v');
    }
}
