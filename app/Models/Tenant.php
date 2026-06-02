<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Throwable;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    use HasFactory;

    protected $fillable = [
        'id',
        'plan_id',
        'name',
        'email',
        'address',
        'admin_name',
        'status',
        'subscription_due_at',
        'update_required',
        'update_required_version',
    ];

    protected $hidden = [
        'data',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'plan_id',
            'name',
            'email',
            'address',
            'admin_name',
            'status',
            'subscription_due_at',
            'update_required',
            'update_required_version',
            'created_at',
            'updated_at',
        ];
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    protected function casts(): array
    {
        return [
            'subscription_due_at'     => 'date',
            'update_required'         => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function billingInvoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function getFullDomain(): string
    {
        $domain = $this->domains()->value('domain');

        if ($domain === null) {
            $domain = "{$this->id}.".config('tenancy.tenant_base_domain', 'localhost');
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';

        return "{$scheme}://{$domain}";
    }

    public function hashedDatabaseName(): string
    {
        $prefix = (string) config('tenancy.database.prefix', 'tenant_');
        $suffix = (string) config('tenancy.database.suffix', '');
        $appKey = (string) config('app.key', 'paymonitor');
        $hash = substr(hash_hmac('sha256', (string) $this->getTenantKey(), $appKey), 0, 32);

        return $prefix.$hash.$suffix;
    }

    public function legacyDatabaseName(): string
    {
        return (string) config('tenancy.database.prefix', 'tenant_')
            .$this->getTenantKey()
            .(string) config('tenancy.database.suffix', '');
    }

    public function preferredDatabaseName(): string
    {
        $hashedName = $this->hashedDatabaseName();

        try {
            if ($this->database()->manager()->databaseExists($hashedName)) {
                return $hashedName;
            }
        } catch (Throwable) {
            return $hashedName;
        }

        $legacyName = $this->legacyDatabaseName();

        try {
            if ($this->database()->manager()->databaseExists($legacyName)) {
                return $legacyName;
            }
        } catch (Throwable) {
            return $hashedName;
        }

        return $hashedName;
    }

    public function isOverdue(): bool
    {
        if ($this->subscription_due_at === null) {
            return false;
        }

        return $this->subscription_due_at->lt(today());
    }

    public function resolvedPortalStatus(): string
    {
        if ($this->isOverdue() || $this->status === 'overdue') {
            return 'overdue';
        }

        if (in_array($this->status, ['suspended', 'inactive'], true)) {
            return (string) $this->status;
        }

        return 'active';
    }

    public function accessBlocked(): bool
    {
        return $this->resolvedPortalStatus() !== 'active';
    }

    public function accessBlockedMessage(): string
    {
        return match ($this->resolvedPortalStatus()) {
            'overdue' => 'This cooperative portal is unavailable because the subscription plan is overdue.',
            'suspended' => 'This cooperative portal is currently suspended.',
            'inactive' => 'This cooperative portal is currently inactive.',
            default => 'This cooperative portal is currently unavailable.',
        };
    }

    public function hasPlanFeature(string $feature): bool
    {
        $this->loadMissing('plan');

        return $this->plan?->hasFeature($feature) ?? false;
    }

    public function supportsAuditLogs(): bool
    {
        return $this->hasPlanFeature('audit_logs');
    }

    public function getUsage(): int
    {
        try {
            return (int) $this->run(static function (): int {
                return collect([
                    'branches',
                    'users',
                    'members',
                    'loan_types',
                    'loans',
                ])->sum(static function (string $table): int {
                    if (! Schema::hasTable($table)) {
                        return 0;
                    }

                    return DB::table($table)->count();
                });
            });
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Get all update records for this tenant.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(TenantUpdate::class);
    }

    /**
     * Get the current version tag for this tenant.
     */
    public function currentVersion(): string
    {
        $current = $this->updates()
            ->where('is_current', true)
            ->with('appRelease')
            ->first();

        return $current?->appRelease?->tag ?? 'v0.0.0';
    }
}
