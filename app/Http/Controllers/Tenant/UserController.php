<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreUserRequest;
use App\Http\Requests\Tenant\UpdateUserRequest;
use App\Mail\TenantWelcomeMail;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        TenantPermissions::ensureConfigured();

        $roles = $this->availableRoles();

        $filters = $request->validate([
            'role' => ['nullable', Rule::in($roles->pluck('name')->all())],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->with(['branch', 'roles'])
            ->when($filters['role'] ?? null, static function ($query, string $role): void {
                $query->whereHas('roles', static function ($roleQuery) use ($role): void {
                    $roleQuery->where('name', $role);
                });
            })
            ->when($filters['branch_id'] ?? null, static fn ($query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['status'] ?? null, static function ($query, string $status): void {
                $query->where('is_active', $status === 'active');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::query()->orderBy('name')->get();

        return view('users.index', compact('users', 'branches', 'filters', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);
        TenantPermissions::ensureConfigured();

        $branches = Branch::query()->orderBy('name')->get();
        $generatedPassword = old('generated_password', Str::upper(Str::random(10)));
        $selectedRole = (string) old('role', 'viewer');
        $selectedPermissions = old('permissions', TenantPermissions::permissionsForRole($selectedRole));
        $roles = $this->availableRoles();

        return view('users.create', [
            'branches' => $branches,
            'roles' => $roles,
            'generatedPassword' => $generatedPassword,
            'selectedRole' => $selectedRole,
            'permissionGroups' => TenantPermissions::groupedAssignable(),
            'permissionLabels' => TenantPermissions::labels(),
            'selectedPermissions' => $selectedPermissions,
            'rolePermissionMap' => $this->rolePermissionMap($roles),
        ]);
    }

    public function store(StoreUserRequest $request): View
    {
        $this->authorize('create', User::class);
        TenantPermissions::ensureConfigured();

        $validated = $request->validated();
        $password = $validated['generated_password'] ?? Str::upper(Str::random(10));

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $password,
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->syncUserAccess($request, $user, $validated['role'], $validated['permissions'] ?? null);

        $currentTenant = tenant();

        if ($currentTenant instanceof Tenant) {
            Mail::to($user->email)->send(new TenantWelcomeMail(
                $currentTenant,
                $user->email,
                $password,
                rtrim($currentTenant->getFullDomain(), '/').'/login',
            ));
        }

        return view('users.password', [
            'user' => $user,
            'password' => $password,
            'credentialsEmailed' => true,
        ]);
    }

    public function show(string $tenant, User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['branch', 'roles']);

        return view('users.show', [
            'user' => $user,
            'permissionLabels' => TenantPermissions::labels(),
            'selectedPermissions' => $user->permissionMatrixSelection(),
            'usesCustomPermissions' => $user->hasCustomTenantPermissions(),
        ]);
    }

    public function edit(string $tenant, User $user): View
    {
        $this->authorize('update', $user);
        TenantPermissions::ensureConfigured();

        $branches = Branch::query()->orderBy('name')->get();
        $roles = $this->availableRoles();
        $user->load(['branch', 'roles']);

        return view('users.edit', [
            'user' => $user,
            'branches' => $branches,
            'roles' => $roles,
            'permissionGroups' => TenantPermissions::groupedAssignable(),
            'permissionLabels' => TenantPermissions::labels(),
            'selectedPermissions' => old('permissions', $user->permissionMatrixSelection()),
            'rolePermissionMap' => $this->rolePermissionMap($roles),
        ]);
    }

    public function update(UpdateUserRequest $request, string $tenant, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        TenantPermissions::ensureConfigured();

        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->syncUserAccess($request, $user, $validated['role'], $validated['permissions'] ?? null);

        return redirect('/users/'.$user->id)->with('success', 'User updated successfully.');
    }

    public function resendCredentials(string $tenant, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $currentTenant = tenant();

        if (! $currentTenant instanceof Tenant) {
            return redirect('/users/'.$user->id)->with('error', 'Tenant context could not be resolved.');
        }

        $temporaryPassword = Str::upper(Str::random(10));

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'remember_token' => Str::random(60),
        ])->save();

        Mail::to($user->email)->send(new TenantWelcomeMail(
            $currentTenant,
            $user->email,
            $temporaryPassword,
            rtrim($currentTenant->getFullDomain(), '/').'/login',
        ));

        return redirect('/users/'.$user->id)->with('success', "Credentials resent to {$user->email}.");
    }

    public function destroy(string $tenant, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if (auth()->id() === $user->id) {
            return redirect('/users')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect('/users')->with('success', 'User deleted successfully.');
    }

    /**
     * @param  array<int, string>|null  $selectedPermissions
     * @return array<int, string>
     */
    protected function resolvePermissionSelection(Request $request, string $role, ?array $selectedPermissions): array
    {
        $permissions = $request->boolean('permissions_present')
            ? ($selectedPermissions ?? [])
            : TenantPermissions::permissionsForRole($role);

        return array_values(array_unique(array_filter($permissions, static fn (string $permission): bool => in_array($permission, TenantPermissions::assignable(), true))));
    }

    protected function syncUserAccess(Request $request, User $user, string $role, ?array $selectedPermissions): void
    {
        $user->syncRoles([$role]);

        $resolvedPermissions = $this->resolvePermissionSelection($request, $role, $selectedPermissions);
        $rolePermissions = TenantPermissions::permissionsForRole($role);

        sort($resolvedPermissions);
        sort($rolePermissions);

        if ($resolvedPermissions === $rolePermissions) {
            $user->syncPermissions([]);

            return;
        }

        $user->syncPermissions([
            TenantPermissions::RBAC_CUSTOMIZED,
            ...$resolvedPermissions,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    protected function availableRoles()
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

    /**
     * @param  \Illuminate\Support\Collection<int, Role>  $roles
     * @return array<string, array<int, string>>
     */
    protected function rolePermissionMap($roles): array
    {
        $map = [];

        foreach ($roles as $role) {
            $map[$role->name] = TenantPermissions::permissionsForRole($role->name);
        }

        return $map;
    }
}
