<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Support\TenantPermissions;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::MEMBERS_VIEW, [
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
        return $user->hasTenantPermission(TenantPermissions::MEMBERS_CREATE, ['tenant_admin', 'branch_manager', 'loan_officer']);
    }

    public function update(User $user, Member $member): bool
    {
        return $user->hasTenantPermission(TenantPermissions::MEMBERS_UPDATE, ['tenant_admin', 'branch_manager', 'loan_officer']);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->hasTenantPermission(TenantPermissions::MEMBERS_DELETE, ['tenant_admin']);
    }
}
