<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LoanPayment;
use App\Models\User;

class LoanPaymentPolicy
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

    public function view(User $user, LoanPayment $loanPayment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['tenant_admin', 'branch_manager', 'loan_officer', 'cashier']);
    }

    public function update(User $user, LoanPayment $loanPayment): bool
    {
        return $user->hasRole('tenant_admin');
    }

    public function delete(User $user, LoanPayment $loanPayment): bool
    {
        return $user->hasRole('tenant_admin');
    }
}
