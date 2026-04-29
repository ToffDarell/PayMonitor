<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreTenantRequest;
use App\Http\Requests\Central\UpdateTenantRequest;
use App\Models\BillingInvoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantService;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService,
    ) {}

    public function index(): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        $tenants = Tenant::with(['plan', 'domains'])->latest()->paginate(20);

        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function create(): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $plans = Plan::query()->orderBy('price')->get();

        return view('superadmin.tenants.create', compact('plans'));
    }

    public function store(StoreTenantRequest $request): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validated();
        $this->tenantService->createTenant($validated);

        return redirect()->route('superadmin.tenants.index')->with('success', "Tenant created. Credentials sent to {$validated['admin_email']}");
    }

    public function show(Tenant $tenant): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $tenant->loadMissing('plan', 'domains');
        $usage = $this->tenantService->getTenantUsage($tenant);
        $primaryDomain = $tenant->domains->first();

        return view('superadmin.tenants.show', compact('tenant', 'usage', 'primaryDomain'));
    }

    public function edit(Tenant $tenant): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $tenant->loadMissing('domains', 'plan');
        $plans = Plan::query()->orderBy('price')->get();
        $primaryDomain = $tenant->domains->first();

        return view('superadmin.tenants.edit', compact('tenant', 'plans', 'primaryDomain'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $tenant->update($request->validated());
        $tenant->refresh()->loadMissing('plan');

        BillingInvoice::syncOpenInvoiceForTenant(
            $tenant,
            'Synced from superadmin tenant update.',
        );

        return redirect()->route('superadmin.tenants.index')->with('success', 'Tenant updated.');
    }

    public function destroy(Tenant $tenant): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $tenant->delete();

        return redirect()->route('superadmin.tenants.index')->with('success', 'Tenant deleted.');
    }
}
