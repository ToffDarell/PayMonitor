<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        $plans = Plan::latest()->paginate(15);

        return view('superadmin.plans.index', compact('plans'));
    }

    public function create(): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        return view('superadmin.plans.create');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'price'        => ['required', 'numeric', 'min:0'],
            'max_branches' => ['required', 'integer', 'min:0'],
            'max_users'    => ['required', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
            'features'     => ['nullable', 'array'],
            'features.*'   => ['string'],
        ]);

        if (!isset($validated['features'])) {
            $validated['features'] = [];
        }

        Plan::create($validated);

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan created.');
    }

    public function edit(Plan $plan): \Illuminate\View\View
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        return view('superadmin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'price'        => ['required', 'numeric', 'min:0'],
            'max_branches' => ['required', 'integer', 'min:0'],
            'max_users'    => ['required', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
            'features'     => ['nullable', 'array'],
            'features.*'   => ['string'],
        ]);

        if (!isset($validated['features'])) {
            $validated['features'] = [];
        }

        $plan->update($validated);

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan): \Illuminate\Http\RedirectResponse
    {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        $plan->delete();

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan deleted.');
    }
}