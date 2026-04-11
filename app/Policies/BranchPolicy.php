<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Support\TenantPermissions;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::BRANCHES_VIEW, ['tenant_admin']);
    }

    public function view(User $user, Branch $branch): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::BRANCHES_CREATE, ['tenant_admin']);
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasTenantPermission(TenantPermissions::BRANCHES_UPDATE, ['tenant_admin']);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasTenantPermission(TenantPermissions::BRANCHES_DELETE, ['tenant_admin']);
    }
}
