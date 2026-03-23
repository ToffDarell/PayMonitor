<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use App\Models\Plan;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        $tenants = Tenant::with('plan')->latest()->paginate(20);

        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function create(): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $plans = Plan::where('is_active', true)->get();

        return view('superadmin.tenants.create', compact('plans'));
    }

    public function store(TenantRequest $request): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        Tenant::create($request->validated());

        return redirect()->route('superadmin.tenants.index')->with('success', 'Tenant created.');
    }

    public function show(Tenant $tenant): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $tenant->load('plan', 'branches', 'users');

        return view('superadmin.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $plans = Plan::where('is_active', true)->get();

        return view('superadmin.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(TenantRequest $request, Tenant $tenant): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $tenant->update($request->validated());

        return redirect()->route('superadmin.tenants.index')->with('success', 'Tenant updated.');
    }

    public function destroy(Tenant $tenant): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $tenant->delete();

        return redirect()->route('superadmin.tenants.index')->with('success', 'Tenant deleted.');
    }
}