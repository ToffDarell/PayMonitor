<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(static function ($request, $next) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $totalTenants = Tenant::query()->count();
        $activeTenants = Tenant::query()->where('status', 'active')->count();
        $overdueTenants = Tenant::query()->where('status', 'overdue')->count();
        $suspendedTenants = Tenant::query()->where('status', 'suspended')->count();
        $inactiveTenants = Tenant::query()->where('status', 'inactive')->count();

        $monthlyRevenue = Tenant::query()
            ->with('plan')
            ->where('status', 'active')
            ->get()
            ->sum(static fn (Tenant $tenant): float => (float) ($tenant->plan?->price ?? 0));

        $recentTenants = Tenant::query()
            ->with('plan')
            ->latest()
            ->limit(10)
            ->get();

        return view('central.dashboard', compact(
            'totalTenants',
            'activeTenants',
            'overdueTenants',
            'suspendedTenants',
            'inactiveTenants',
            'monthlyRevenue',
            'recentTenants',
        ));
    }
}
