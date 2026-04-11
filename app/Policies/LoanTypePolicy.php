<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LoanType;
use App\Models\User;
use App\Support\TenantPermissions;

class LoanTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::LOAN_TYPES_VIEW, ['tenant_admin', 'branch_manager']);
    }

    public function view(User $user, LoanType $loanType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::LOAN_TYPES_CREATE, ['tenant_admin', 'branch_manager']);
    }

    public function update(User $user, LoanType $loanType): bool
    {
        return $user->hasTenantPermission(TenantPermissions::LOAN_TYPES_UPDATE, ['tenant_admin', 'branch_manager']);
    }

    public function delete(User $user, LoanType $loanType): bool
    {
        return $user->hasTenantPermission(TenantPermissions::LOAN_TYPES_DELETE, ['tenant_admin', 'branch_manager']);
    }
}
