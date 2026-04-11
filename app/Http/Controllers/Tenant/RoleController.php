<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeRoleAction(TenantPermissions::USERS_VIEW);
        TenantPermissions::ensureConfigured();

        $roles = $this->availableRoles()
            ->load(['permissions:id,name'])
            ->loadCount('users');

        return view('users.roles.index', [
            'roles' => $roles,
            'permissionLabels' => TenantPermissions::labels(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeRoleAction(TenantPermissions::USERS_CREATE);
        TenantPermissions::ensureConfigured();

        return view('users.roles.create', [
            'permissionGroups' => TenantPermissions::groupedAssignable(),
            'permissionLabels' => TenantPermissions::labels(),
            'selectedPermissions' => old('permissions', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoleAction(TenantPermissions::USERS_CREATE);
        TenantPermissions::ensureConfigured();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(TenantPermissions::assignable())],
        ]);

        $roleName = $this->normalizeRoleName($validated['name']);

        if ($roleName === '' || TenantPermissions::isSystemRole($roleName)) {
            return back()
                ->withErrors(['name' => 'Please choose a different custom role name.'])
                ->withInput();
        }

        if (Role::query()->where('guard_name', 'web')->where('name', $roleName)->exists()) {
            return back()
                ->withErrors(['name' => 'That role name already exists.'])
                ->withInput();
        }

        $role = Role::query()->create([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions']);

        return redirect()
            ->route('users.roles.index', $this->tenantRouteParameter())
            ->with('success', 'Custom role created successfully.');
    }

    public function edit(Request $request, string $tenant, Role $role): View|RedirectResponse
    {
        $this->authorizeRoleAction(TenantPermissions::USERS_UPDATE);
        TenantPermissions::ensureConfigured();

        if ($redirect = $this->rejectSystemRoleEdit($role)) {
            return $redirect;
        }

        return view('users.roles.edit', [
            'role' => $role->load(['permissions:id,name']),
            'permissionGroups' => TenantPermissions::groupedAssignable(),
            'permissionLabels' => TenantPermissions::labels(),
            'selectedPermissions' => old('permissions', $role->permissions->pluck('name')->values()->all()),
        ]);
    }

    public function update(Request $request, string $tenant, Role $role): RedirectResponse
    {
        $this->authorizeRoleAction(TenantPermissions::USERS_UPDATE);
        TenantPermissions::ensureConfigured();

        if ($redirect = $this->rejectSystemRoleEdit($role)) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(TenantPermissions::assignable())],
        ]);

        $roleName = $this->normalizeRoleName($validated['name']);

        if ($roleName === '' || TenantPermissions::isSystemRole($roleName)) {
            return back()
                ->withErrors(['name' => 'Please choose a different custom role name.'])
                ->withInput();
        }

        if (Role::query()
            ->where('guard_name', 'web')
            ->where('name', $roleName)
            ->whereKeyNot($role->getKey())
            ->exists()) {
            return back()
                ->withErrors(['name' => 'That role name already exists.'])
                ->withInput();
        }

        $role->update(['name' => $roleName]);
        $role->syncPermissions($validated['permissions']);

        return redirect()
            ->route('users.roles.index', $this->tenantRouteParameter())
            ->with('success', 'Custom role updated successfully.');
    }

    public function destroy(Request $request, string $tenant, Role $role): RedirectResponse
    {
        $this->authorizeRoleAction(TenantPermissions::USERS_DELETE);
        TenantPermissions::ensureConfigured();

        if ($redirect = $this->rejectSystemRoleEdit($role)) {
            return $redirect;
        }

        $role->loadCount('users');

        if (($role->users_count ?? 0) > 0) {
            return redirect()
                ->route('users.roles.index', $this->tenantRouteParameter())
                ->with('error', 'This role is still assigned to one or more users.');
        }

        $role->delete();

        return redirect()
            ->route('users.roles.index', $this->tenantRouteParameter())
            ->with('success', 'Custom role deleted successfully.');
    }

    protected function authorizeRoleAction(string $permission): void
    {
        $user = request()->user();

        abort_if(
            $user === null || ! method_exists($user, 'hasTenantPermission') || ! $user->hasTenantPermission($permission, ['tenant_admin']),
            403,
            'Unauthorized access'
        );
    }

    /**
     * @return Collection<int, Role>
     */
    protected function availableRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->orderByRaw("
                CASE
                    WHEN name = 'tenant_admin' THEN 0
                    WHEN name = 'branch_manager' THEN 1
                    WHEN name = 'loan_officer' THEN 2
                    WHEN name = 'cashier' THEN 3
                    WHEN name = 'viewer' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('name')
            ->get();
    }

    protected function normalizeRoleName(string $name): string
    {
        return (string) Str::of($name)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    protected function tenantRouteParameter(): array
    {
        return ['tenant' => request()->route('tenant')];
    }

    protected function rejectSystemRoleEdit(Role $role): ?RedirectResponse
    {
        if (! TenantPermissions::isSystemRole($role->name)) {
            return null;
        }

        return redirect()
            ->route('users.roles.index', $this->tenantRouteParameter())
            ->with('error', 'Default system roles cannot be edited or deleted.');
    }
}
