<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): \Illuminate\View\View
    {
        $branches = Branch::where('tenant_id', auth()->user()->tenant_id)->get();

        return view('users.create', compact('branches'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
            'role'      => ['required', 'in:admin,manager,staff'],
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        User::create([
            'tenant_id' => auth()->user()->tenant_id,
            'branch_id' => $request->branch_id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): \Illuminate\View\View
    {
        abort_if($user->tenant_id !== auth()->user()->tenant_id, 403);
        $branches = Branch::where('tenant_id', auth()->user()->tenant_id)->get();

        return view('users.edit', compact('user', 'branches'));
    }

    public function update(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        abort_if($user->tenant_id !== auth()->user()->tenant_id, 403);

        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email,' . $user->id],
            'role'      => ['required', 'in:admin,manager,staff'],
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $user->update($request->only('name', 'email', 'role', 'branch_id'));

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): \Illuminate\Http\RedirectResponse
    {
        abort_if($user->tenant_id !== auth()->user()->tenant_id, 403);
        abort_if($user->id === auth()->id(), 403);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}