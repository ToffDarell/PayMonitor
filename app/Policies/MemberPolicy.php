<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
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

    public function view(User $user, Member $member): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['tenant_admin', 'branch_manager', 'loan_officer']);
    }

    public function update(User $user, Member $member): bool
    {
        return $user->hasAnyRole(['tenant_admin', 'branch_manager', 'loan_officer']);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->hasRole('tenant_admin');
    }
}
