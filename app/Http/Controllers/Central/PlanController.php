<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StorePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
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
        $plans = Plan::query()
            ->withCount('tenants')
            ->orderBy('price')
            ->get();

        return view('central.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('central.plans.create');
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['features'] = $request->input('features', []);

        if (blank($data['description'] ?? null)) {
            $data['description'] = Plan::defaultDescription();
        }

        Plan::query()->create($data);

        return redirect('/central/plans')->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan): View
    {
        return view('central.plans.edit', compact('plan'));
    }

    public function update(StorePlanRequest $request, Plan $plan): RedirectResponse
    {
        $data = $request->validated();
        $data['features'] = $request->input('features', []);

        if (blank($data['description'] ?? null)) {
            $data['description'] = Plan::defaultDescription();
        }

        $plan->update($data);

        return redirect('/central/plans')->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->loadCount('tenants');

        if ($plan->tenants_count > 0) {
            return redirect('/central/plans')->with('error', 'Cannot delete plan with active tenants');
        }

        $plan->delete();

        return redirect('/central/plans')->with('success', 'Plan deleted successfully.');
    }
}
