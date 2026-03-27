<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\TenantApplication;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function create(Request $request)
    {
        $plans = Plan::query()
            ->orderBy('price')
            ->orderBy('name')
            ->get();

        $requestedPlan = trim((string) $request->query('plan', ''));
        $selectedPlan = null;

        if ($requestedPlan !== '') {
            $selectedPlan = $plans->firstWhere('id', (int) $requestedPlan)?->id;

            if ($selectedPlan === null) {
                $normalizedPlan = strtolower(str_replace('-', ' ', $requestedPlan));

                $selectedPlan = $plans->first(function (Plan $plan) use ($normalizedPlan): bool {
                    return strtolower($plan->name) === $normalizedPlan;
                })?->id;
            }
        }

        if ($selectedPlan === null) {
            $defaultPlan = $plans->first(function (Plan $plan): bool {
                return strtolower($plan->name) === 'standard';
            }) ?? $plans->values()->get(1) ?? $plans->first();

            $selectedPlan = $defaultPlan?->id;
        }

        return view('apply', [
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cooperative_name' => 'required|string|max:255',
            'cda_registration_number' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'plan' => 'nullable|integer|exists:plans,id',
        ]);

        TenantApplication::create([
            'cooperative_name' => $validated['cooperative_name'],
            'cda_registration_number' => $validated['cda_registration_number'] ?? null,
            'admin_name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'admin_email' => $validated['email'],
            'contact_number' => $validated['phone'],
            'email' => $validated['email'],
            'plan_id' => $validated['plan'] ?? null,
            'status' => 'pending'
        ]);
        
        return redirect()->route('apply.thank-you');
    }
}
