<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LoanPayment;
use App\Models\User;
use App\Support\TenantPermissions;

class LoanPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::LOAN_PAYMENTS_VIEW, [
            'tenant_admin',
            'branch_manager',
            'loan_officer',
            'cashier',
            'viewer',
        ]);
    }

    public function view(User $user, LoanPayment $loanPayment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::LOAN_PAYMENTS_CREATE, ['tenant_admin', 'branch_manager', 'loan_officer', 'cashier']);
    }

    public function update(User $user, LoanPayment $loanPayment): bool
    {
        return $user->hasTenantPermission(TenantPermissions::LOAN_PAYMENTS_UPDATE, ['tenant_admin']);
    }

    public function delete(User $user, LoanPayment $loanPayment): bool
    {
        return $user->hasTenantPermission(TenantPermissions::LOAN_PAYMENTS_DELETE, ['tenant_admin']);
    }
}
