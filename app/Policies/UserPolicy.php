<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('tenant_admin');
    }

    public function view(User $user, User $managedUser): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $managedUser): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $this->viewAny($user);
    }
}
