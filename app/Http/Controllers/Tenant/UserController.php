<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreUserRequest;
use App\Http\Requests\Tenant\UpdateUserRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->validate([
            'role' => ['nullable', Rule::in(['tenant_admin', 'branch_manager', 'loan_officer', 'cashier', 'viewer'])],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
        ]);

        $users = User::query()
            ->with(['branch', 'roles'])
            ->when($filters['role'] ?? null, static function ($query, string $role): void {
                $query->whereHas('roles', static function ($roleQuery) use ($role): void {
                    $roleQuery->where('name', $role);
                });
            })
            ->when($filters['branch_id'] ?? null, static fn ($query, int $branchId) => $query->where('branch_id', $branchId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::query()->orderBy('name')->get();

        return view('users.index', compact('users', 'branches', 'filters'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $branches = Branch::query()->orderBy('name')->get();
        $generatedPassword = old('generated_password', Str::upper(Str::random(10)));

        return view('users.create', compact('branches', 'generatedPassword'));
    }

    public function store(StoreUserRequest $request): View
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();
        $password = $validated['generated_password'] ?? Str::upper(Str::random(10));

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $password,
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        return view('users.password', compact('user', 'password'));
    }

    public function show(string $tenant, User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['branch', 'roles']);

        return view('users.show', compact('user'));
    }

    public function edit(string $tenant, User $user): View
    {
        $this->authorize('update', $user);

        $branches = Branch::query()->orderBy('name')->get();
        $user->load(['branch', 'roles']);

        return view('users.edit', compact('user', 'branches'));
    }

    public function update(UpdateUserRequest $request, string $tenant, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect('/users/'.$user->id)->with('success', 'User updated successfully.');
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
}
