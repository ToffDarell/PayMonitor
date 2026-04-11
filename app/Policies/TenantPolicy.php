<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantPermissions;

class TenantPolicy
{
    public function viewDashboard(User $user, Tenant $tenant): bool
    {
        return $user->hasTenantPermission(TenantPermissions::DASHBOARD_VIEW, [
            'tenant_admin',
            'branch_manager',
            'loan_officer',
            'cashier',
            'viewer',
        ]);
    }

    public function viewReports(User $user, Tenant $tenant): bool
    {
        return $user->hasTenantPermission(TenantPermissions::REPORTS_VIEW, [
            'tenant_admin',
            'branch_manager',
            'loan_officer',
            'cashier',
            'viewer',
        ]);
    }
}
