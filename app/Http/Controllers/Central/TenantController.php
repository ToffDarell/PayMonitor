<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreTenantRequest;
use App\Http\Requests\Central\UpdateTenantRequest;
use App\Models\BillingInvoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(private TenantService $tenantService)
    {
        $this->middleware(static function ($request, $next) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $tenants = Tenant::query()
            ->with('plan')
            ->latest()
            ->paginate(15);

        $tenants->setCollection($tenants->getCollection()->map(function (Tenant $tenant): Tenant {
            $tenant->setAttribute('usage', $this->tenantService->getTenantUsage($tenant));
            $tenant->setAttribute('db_size', $this->tenantService->getTenantDatabaseSize($tenant));

            return $tenant;
        }));

        return view('central.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        $plans = Plan::query()->orderBy('price')->get();

        return view('central.tenants.create', compact('plans'));
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->tenantService->createTenant($validated);

        return redirect('/central/tenants')
            ->with('success', "Tenant created. Credentials sent to {$validated['admin_email']}");
    }

    public function show(Tenant $tenant): View
    {
        $tenant->loadMissing('plan', 'domains');

        $usage = $this->tenantService->getTenantUsage($tenant);
        $primaryDomain = $tenant->domains->first();

        return view('central.tenants.show', compact('tenant', 'usage', 'primaryDomain'));
    }

    public function edit(Tenant $tenant): View
    {
        $plans = Plan::query()->orderBy('price')->get();

        return view('central.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($request->validated());
        $tenant->refresh()->loadMissing('plan');

        BillingInvoice::syncOpenInvoiceForTenant(
            $tenant,
            'Synced from tenant subscription update.',
        );

        return redirect('/central/tenants')->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->domains()->delete();
        $tenant->delete();

        return redirect('/central/tenants')->with('success', 'Tenant deleted successfully.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->suspendTenant($tenant);

        return back()->with('success', 'Tenant suspended successfully.');
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->activateTenant($tenant);

        return back()->with('success', 'Tenant activated successfully.');
    }

    public function resendCredentials(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->resendCredentials($tenant);

        return back()->with('success', 'Credentials resent to tenant admin');
    }
}
