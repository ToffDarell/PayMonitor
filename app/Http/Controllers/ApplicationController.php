<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\TenantApplication;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
            'plan' => 'required|integer|exists:plans,id',
            'payment_reference' => 'nullable|string|max:255',
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $plan = Plan::query()->findOrFail((int) $validated['plan']);
        $paymentProof = $request->file('payment_proof');

        if (! $paymentProof instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'payment_proof' => 'Please upload a valid proof of payment file.',
            ]);
        }

        if (! $paymentProof->isValid()) {
            $message = $paymentProof->getErrorMessage();

            if (in_array($paymentProof->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $message = 'The selected proof of payment is too large. Please use a file smaller than 5 MB.';
            }

            throw ValidationException::withMessages([
                'payment_proof' => $message,
            ]);
        }

        try {
            $paymentProofPath = 'application-payments/'.$paymentProof->hashName();
            $stored = Storage::put($paymentProofPath, $paymentProof->get());

            if (! $stored) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'The selected proof of payment could not be saved. Please try again.',
                ]);
            }
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'payment_proof' => 'The selected proof of payment could not be saved. Please choose the file again and try once more.',
            ]);
        }

        TenantApplication::create([
            'cooperative_name' => $validated['cooperative_name'],
            'cda_registration_number' => $validated['cda_registration_number'] ?? null,
            'admin_name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'admin_email' => $validated['email'],
            'contact_number' => $validated['phone'],
            'email' => $validated['email'],
            'plan_id' => $plan->id,
            'payment_amount' => $plan->price,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'payment_proof_path' => $paymentProofPath,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
        
        return redirect()->route('apply.thank-you');
    }
}
