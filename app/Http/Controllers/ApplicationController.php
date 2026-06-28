<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\TenantApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function create(Request $request): View
    {
        $monthlyPlan = Plan::where('name', 'Professional Monthly')->firstOrFail();
        $yearlyPlan = Plan::where('name', 'Professional Yearly')->firstOrFail();
        $cycle = in_array($request->query('cycle'), ['monthly', 'yearly']) ? $request->query('cycle') : 'monthly';
        return view('apply', compact('monthlyPlan', 'yearlyPlan', 'cycle'));
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

        // Free plan — auto-verify, no payment needed
        if ((float) $plan->price === 0.0) {
            $application->update([
                'payment_status' => 'verified',
                'paid_at'        => now(),
            ]);

            return redirect()->route('apply.thank-you')
                ->with('message_type', 'free');
        }

        // Paid plan — create PayMongo checkout session
        $amount = (int) round($plan->price * 100);

        $response = Http::acceptJson()
            ->withBasicAuth(config('paymongo.secret_key'), '')
            ->post('https://api.paymongo.com/v1/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'line_items' => [
                            [
                                'amount'      => $amount,
                                'currency'    => 'PHP',
                                'name'        => 'PayMonitor — ' . $plan->name,
                                'quantity'    => 1,
                            ],
                        ],
                        'payment_method_types' => ['card', 'gcash', 'paymaya', 'qrph'],
                        'description'          => 'Application Fee for ' . $application->cooperative_name,
                        'success_url'          => route('apply.payment-callback', ['applicationId' => $application->id], true),
                        'cancel_url'           => route('apply.payment-pending', ['applicationId' => $application->id], true),
                    ],
                ],
            ]);

        if ($response->failed()) {
            return redirect()->route('apply.create')
                ->withInput()
                ->with('error', 'Could not initiate payment. Please try again.');
        }

        $data = $response->json();

        $application->update([
            'paymongo_link_id' => Arr::get($data, 'data.id'),
            'payment_url'      => Arr::get($data, 'data.attributes.checkout_url'),
        ]);

        return redirect()->away(Arr::get($data, 'data.attributes.checkout_url'));
    }

    public function paymentCallback(Request $request): RedirectResponse
    {
        $applicationId = $request->query('applicationId');
        $application   = TenantApplication::findOrFail($applicationId);

        if ($application->paymongo_link_id) {
            $response = Http::acceptJson()
                ->withBasicAuth(config('paymongo.secret_key'), '')
                ->get('https://api.paymongo.com/v1/checkout_sessions/' . $application->paymongo_link_id);

            if ($response->successful()) {
                $payments = Arr::get($response->json(), 'data.attributes.payments', []);
                $payment  = $payments[0] ?? null;
                $status   = $payment ? Arr::get($payment, 'attributes.status') : 'unpaid';

                if ($status === 'paid') {
                    $application->update([
                        'payment_status'      => 'verified',
                        'paymongo_payment_id' => Arr::get($payment, 'id'),
                        'paid_at'             => now(),
                        'payment_method'      => Arr::get($payment, 'attributes.source.type'),
                    ]);

                    return redirect()->route('apply.thank-you')
                        ->with('message_type', 'paid');
                }
            }
        }

        return redirect()->route('apply.payment-pending', ['applicationId' => $application->id]);
    }

    public function paymentPending(int $applicationId): View
    {
        $application = TenantApplication::with('plan')->findOrFail($applicationId);
        return view('apply-payment-pending', compact('application'));
    }

    public function verifyPayment(int $applicationId): RedirectResponse
    {
        $application = TenantApplication::findOrFail($applicationId);

        if ($application->payment_status === 'verified') {
            return redirect()->route('apply.thank-you')
                ->with('message_type', 'paid');
        }

        if ($application->paymongo_link_id) {
            $response = Http::acceptJson()
                ->withBasicAuth(config('paymongo.secret_key'), '')
                ->get('https://api.paymongo.com/v1/checkout_sessions/' . $application->paymongo_link_id);

            if ($response->successful()) {
                $payments = Arr::get($response->json(), 'data.attributes.payments', []);
                $payment  = $payments[0] ?? null;
                $status   = $payment ? Arr::get($payment, 'attributes.status') : 'unpaid';

                if ($status === 'paid') {
                    $application->update([
                        'payment_status'      => 'verified',
                        'paymongo_payment_id' => Arr::get($payment, 'id'),
                        'paid_at'             => now(),
                        'payment_method'      => Arr::get($payment, 'attributes.source.type'),
                    ]);

                    return redirect()->route('apply.thank-you')
                        ->with('message_type', 'paid');
                }
            }
        }

        return redirect()->route('apply.payment-pending', ['applicationId' => $application->id])
            ->with('error', 'Payment has not been completed yet. Please try again or contact support.');
    }

    public function thankYou(Request $request): View
    {
        return view('apply-thankyou');
    }
}
