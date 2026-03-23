<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $tenantId = auth()->user()->tenant_id;

        $totalBranches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $activeBranches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        $totalUsers = User::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $adminUsers = User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', 'admin')
            ->count();

        $recentUsers = User::query()
            ->with('branch')
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(5)
            ->get();

        $branchOverview = Branch::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalBranches',
            'activeBranches',
            'totalUsers',
            'adminUsers',
            'recentUsers',
            'branchOverview'
        ));
    }

    public function superAdmin(): \Illuminate\View\View
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $tenants = Tenant::with('plan')->latest()->limit(10)->get();

        return view('superadmin.dashboard', compact('totalTenants', 'activeTenants', 'tenants'));
    }
}
