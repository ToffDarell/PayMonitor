<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\TenantApplication;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function create(Request $request)
    {
        $plan = $request->query('plan', '');
        return view('apply', ['selectedPlan' => $plan]);
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
            'plan' => 'nullable|string'
        ]);

        $plan_id = null;
        if (!empty($validated['plan'])) {
            // Find plan matching the slug/name
            $planRecord = Plan::where('name', 'like', $validated['plan'])->first();
            if ($planRecord) {
                $plan_id = $planRecord->id;
            }
        }

        TenantApplication::create([
            'cooperative_name' => $validated['cooperative_name'],
            'cda_registration_number' => $validated['cda_registration_number'] ?? null,
            'admin_name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'admin_email' => $validated['email'],
            'contact_number' => $validated['phone'],
            'email' => $validated['email'],
            'plan_id' => $plan_id,
            'status' => 'pending'
        ]);
        
        return redirect()->route('apply.thank-you');
    }
}
