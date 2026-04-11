<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\TenantPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\TenantResetPasswordNotification;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        if (tenant() !== null) {
            $tenant = tenant();
            $tenantHost = $tenant?->domains()->value('domain') ?? request()->getHost();
            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';
            $resetUrl = "{$scheme}://{$tenantHost}/reset-password/{$token}?email=".urlencode((string) $this->getEmailForPasswordReset());

            $this->notify(new TenantResetPasswordNotification($resetUrl));

            return;
        }

        $this->notify(new ResetPasswordNotification($token));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function hasCustomTenantPermissions(): bool
    {
        return $this->getDirectPermissions()
            ->pluck('name')
            ->contains(TenantPermissions::RBAC_CUSTOMIZED);
    }

    public function hasDirectTenantPermission(string $permission): bool
    {
        return $this->getDirectPermissions()
            ->pluck('name')
            ->contains($permission);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function hasAnyDirectTenantPermission(array $permissions): bool
    {
        $directPermissions = $this->getDirectPermissions()->pluck('name')->all();

        return array_intersect($permissions, $directPermissions) !== [];
    }

    /**
     * @param  array<int, string>  $fallbackRoles
     */
    public function hasTenantPermission(string $permission, array $fallbackRoles = []): bool
    {
        if ($this->hasRole('tenant_admin')) {
            return true;
        }

        if ($this->hasCustomTenantPermissions()) {
            return $this->hasDirectTenantPermission($permission);
        }

        if ($this->hasPermissionTo($permission)) {
            return true;
        }

        $roles = $fallbackRoles !== [] ? $fallbackRoles : TenantPermissions::rolesFor($permission);

        return $roles !== [] && $this->hasAnyRole($roles);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function hasAnyTenantPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasTenantPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function permissionMatrixSelection(): array
    {
        if ($this->hasCustomTenantPermissions()) {
            return $this->getDirectPermissions()
                ->pluck('name')
                ->reject(static fn (string $permission): bool => $permission === TenantPermissions::RBAC_CUSTOMIZED)
                ->values()
                ->all();
        }

        return TenantPermissions::permissionsForRole($this->getRoleNames()->first() ?? 'viewer');
    }

    public function preferredTenantLandingPath(): string
    {
        $candidates = [
            TenantPermissions::DASHBOARD_VIEW => '/dashboard',
            TenantPermissions::MEMBERS_VIEW => '/members',
            TenantPermissions::LOANS_VIEW => '/loans',
            TenantPermissions::LOAN_PAYMENTS_VIEW => '/loan-payments',
            TenantPermissions::REPORTS_VIEW => '/reports',
            TenantPermissions::LOAN_TYPES_VIEW => '/loan-types',
            TenantPermissions::BRANCHES_VIEW => '/branches',
            TenantPermissions::USERS_VIEW => '/users',
            TenantPermissions::SETTINGS_VIEW => '/settings',
        ];

        foreach ($candidates as $permission => $path) {
            if ($this->hasTenantPermission($permission)) {
                return $path;
            }
        }

        return '/dashboard';
    }
}
