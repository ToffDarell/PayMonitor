<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'tenant_admin',
            'branch_manager',
            'loan_officer',
            'cashier',
            'viewer',
        ]);
    }

    public function view(User $user, Loan $loan): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['tenant_admin', 'branch_manager', 'loan_officer']);
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole(['tenant_admin', 'branch_manager']);
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->hasRole('tenant_admin');
    }

    public function computePreview(User $user): bool
    {
        return $user->hasAnyRole(['tenant_admin', 'branch_manager', 'loan_officer']);
    }
}
