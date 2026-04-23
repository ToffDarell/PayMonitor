<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantUpdate extends Model
{
    use CentralConnection;

    public const STATUS_UPDATE_AVAILABLE = 'update_available';
    public const STATUS_UPDATING = 'updating';
    public const STATUS_UPDATED = 'updated';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    protected $fillable = [
        'tenant_id',
        'app_release_id',
        'status',
        'is_current',
        'applied_at',
        'required_at',
        'grace_until',
        'failure_reason',
        'metadata',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'applied_at' => 'datetime',
        'required_at' => 'datetime',
        'grace_until' => 'datetime',
        'metadata' => 'array',
    ];

    public function appRelease(): BelongsTo
    {
        return $this->belongsTo(AppRelease::class);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeRequiredAndOverdue($query)
    {
        return $query->whereNotNull('required_at')
            ->where(function ($q) {
                $q->whereNull('grace_until')
                    ->orWhere('grace_until', '<', now());
            })
            ->where('status', '!=', self::STATUS_UPDATED);
    }

    public function isOverdue(): bool
    {
        if (!$this->required_at) {
            return false;
        }

        if (!$this->grace_until) {
            return true;
        }

        return now()->isAfter($this->grace_until);
    }
}
