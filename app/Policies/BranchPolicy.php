<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
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

    public function view(User $user, Branch $branch): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('tenant_admin');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasRole('tenant_admin');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasRole('tenant_admin');
    }
}
