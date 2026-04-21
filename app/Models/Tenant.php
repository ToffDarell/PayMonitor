<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            'subscription_due_at' => 'date',
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

    public function isOverdue(): bool
    {
        if ($this->subscription_due_at === null) {
            return false;
        }

        return $this->subscription_due_at->lt(today());
    }

    public function supportsAuditLogs(): bool
    {
        $this->loadMissing('plan');

        return strcasecmp((string) ($this->plan?->name ?? ''), 'Premium') === 0;
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
}
