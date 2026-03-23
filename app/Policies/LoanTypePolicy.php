<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LoanType;
use App\Models\User;

class LoanTypePolicy
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

    public function view(User $user, LoanType $loanType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('tenant_admin');
    }

    public function update(User $user, LoanType $loanType): bool
    {
        return $user->hasRole('tenant_admin');
    }

    public function delete(User $user, LoanType $loanType): bool
    {
        return $user->hasRole('tenant_admin');
    }
}
