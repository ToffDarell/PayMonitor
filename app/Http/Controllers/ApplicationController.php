<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\TenantApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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
            'plans'        => $plans,
            'selectedPlan' => $selectedPlan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cooperative_name'        => 'required|string|max:255',
            'cda_registration_number' => 'nullable|string|max:255',
            'first_name'              => 'required|string|max:255',
            'last_name'               => 'required|string|max:255',
            'email'                   => 'required|email|max:255',
            'phone'                   => 'required|string|max:20',
            'plan'                    => 'required|integer|exists:plans,id',
        ]);

        $plan = Plan::query()->findOrFail((int) $validated['plan']);

        // Create application record first (unpaid)
        $application = TenantApplication::create([
            'cooperative_name'        => $validated['cooperative_name'],
            'cda_registration_number' => $validated['cda_registration_number'] ?? null,
            'admin_name'              => $validated['first_name'] . ' ' . $validated['last_name'],
            'admin_email'             => $validated['email'],
            'contact_number'          => $validated['phone'],
            'email'                   => $validated['email'],
            'plan_id'                 => $plan->id,
            'payment_amount'          => $plan->price,
            'payment_status'          => 'pending',
            'status'                  => 'pending',
        ]);

        // If plan is free (price = 0), skip payment
        if ((float) $plan->price === 0.0) {
            $application->update(['payment_status' => 'verified']);

            return redirect()->route('apply.thank-you')
                ->with('success', 'Application submitted successfully! No payment required for this plan.');
        }

        // Create PayMongo checkout session
        $amount = (int) round($plan->price * 100); // centavos

        $response = Http::withBasicAuth(config('paymongo.secret_key'), '')
            ->post('https://api.paymongo.com/v1/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'line_items' => [
                            [
                                'amount' => $amount,
                                'currency' => 'PHP',
                                'name' => 'PayMonitor Application Fee — ' . $plan->name . ' Plan',
                                'quantity' => 1,
                            ]
                        ],
                        'payment_method_types' => ['card', 'gcash', 'paymaya', 'qrph'],
                        'description' => 'Application Fee for ' . $application->cooperative_name,
                        'success_url' => route('apply.payment-callback', ['applicationId' => $application->id], true),
                        'cancel_url' => route('apply.payment-pending', ['applicationId' => $application->id], true),
                        'send_email_receipt' => true,
                    ],
                ],
            ]);

        if ($response->failed()) {
            // Payment link creation failed — still save application, admin handles manually
            return redirect()->route('apply.thank-you')
                ->with('warning', 'Application submitted. Payment link will be sent to your email shortly.');
        }

        $data        = $response->json();
        $linkId      = $data['data']['id']; // this is now checkout session id
        $checkoutUrl = $data['data']['attributes']['checkout_url'];

        // Save payment link to application
        $application->update([
            'paymongo_link_id' => $linkId,
            'payment_url'      => $checkoutUrl,
            'amount_paid'      => $plan->price,
        ]);

        // Store application ID in session for callback
        session(['pending_application_id' => $application->id]);

        // Redirect applicant to PayMongo checkout
        return redirect($checkoutUrl);
    }

    /**
     * Handle the return redirect from PayMongo after successful payment.
     */
    public function paymentCallback(Request $request): RedirectResponse
    {
        $applicationId = $request->query('applicationId') ?? session('pending_application_id');

        if (! $applicationId) {
            return redirect()->route('apply.create')
                ->with('error', 'Session expired. Please apply again.');
        }

        $application = TenantApplication::find($applicationId);

        if (! $application) {
            return redirect()->route('apply.create')
                ->with('error', 'Application not found. Please apply again.');
        }

        // Verify payment with PayMongo
        $response = Http::withBasicAuth(config('paymongo.secret_key'), '')
            ->get('https://api.paymongo.com/v1/checkout_sessions/' . $application->paymongo_link_id);

        if ($response->successful()) {
            $data     = $response->json();
            $payments = $data['data']['attributes']['payments'] ?? [];
            $payment  = $payments[0] ?? null;

            if ($payment && $payment['attributes']['status'] === 'paid') {
                $application->update([
                    'payment_status'      => 'verified',
                    'paymongo_payment_id' => $payment['id'],
                    'paid_at'             => now(),
                    'payment_method'      => $payment['attributes']['source']['type'] ?? null,
                ]);

                // Clear session
                session()->forget('pending_application_id');

                return redirect()->route('apply.thank-you')
                    ->with('success', 'Payment successful! Your application has been submitted. We will review and send your credentials within 24 hours.');
            }
        }

        // Payment not confirmed yet — send to pending page
        return redirect()->route('apply.payment-pending', $application->id);
    }

    /**
     * Show payment pending page.
     */
    public function paymentPending(int $applicationId)
    {
        $application = TenantApplication::findOrFail($applicationId);

        return view('apply-payment-pending', compact('application'));
    }

    /**
     * Verify payment status manually (called by applicant from pending page).
     */
    public function verifyPayment(int $applicationId): RedirectResponse
    {
        $application = TenantApplication::findOrFail($applicationId);

        if (! $application->paymongo_link_id) {
            return back()->with('error', 'No payment link found for this application.');
        }

        // Already verified — skip API call
        if ($application->payment_status === 'verified') {
            return redirect()->route('apply.thank-you')
                ->with('success', 'Your payment has already been confirmed!');
        }

        $response = Http::withBasicAuth(config('paymongo.secret_key'), '')
            ->get('https://api.paymongo.com/v1/checkout_sessions/' . $application->paymongo_link_id);

        if ($response->successful()) {
            $data     = $response->json();
            $payments = $data['data']['attributes']['payments'] ?? [];
            $payment  = $payments[0] ?? null;

            if ($payment && $payment['attributes']['status'] === 'paid') {
                $application->update([
                    'payment_status'      => 'verified',
                    'paymongo_payment_id' => $payment['id'],
                    'paid_at'             => now(),
                    'payment_method'      => $payment['attributes']['source']['type'] ?? null,
                ]);

                session()->forget('pending_application_id');

                return redirect()->route('apply.thank-you')
                    ->with('success', 'Payment verified! Your application is now under review.');
            }
        }

        return back()->with('error', 'Payment not yet confirmed. Please try again in a few moments.');
    }
}
