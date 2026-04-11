<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\TenantPermissions;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::USERS_VIEW, ['tenant_admin']);
    }

    public function view(User $user, User $managedUser): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasTenantPermission(TenantPermissions::USERS_CREATE, ['tenant_admin']);
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->hasTenantPermission(TenantPermissions::USERS_UPDATE, ['tenant_admin']);
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $user->hasTenantPermission(TenantPermissions::USERS_DELETE, ['tenant_admin']);
    }
}
