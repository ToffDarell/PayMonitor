<?php

namespace App\Http\Controllers\Central;

use App\Mail\ApplicationPaymentVerifiedMail;
use App\Mail\PaymentLinkMail;
use App\Http\Controllers\Controller;
use App\Models\TenantApplication;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = TenantApplication::with('plan')->orderBy('created_at', 'desc');

        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        $applications = $query->paginate(15);
        $pendingCount = TenantApplication::where('status', 'pending')->count();

        return view('central.applications.index', compact('applications', 'pendingCount'));
    }

    public function show(TenantApplication $application)
    {
        $application->load('plan', 'reviewer', 'paymentVerifier');
        $pendingCount = TenantApplication::where('status', 'pending')->count();

        return view('central.applications.show', compact('application', 'pendingCount'));
    }

    public function approve(TenantApplication $application, TenantService $tenantService): RedirectResponse
    {
        if ($application->status !== 'pending') {
            return back()->with('error', 'Only pending applications can be approved.');
        }

        if ($application->payment_status !== 'verified') {
            return back()->with('error', 'Verify the payment before approving this application.');
        }

        // Setup domain name based on cooperative name
        $domainStr = $application->domain;
        $subscriptionDueAt = now()->addDays(30)->toDateString();

        // Create the tenant
        $tenantService->createTenant([
            'name'                => $application->cooperative_name,
            'domain'              => $domainStr,
            'plan_id'             => $application->plan_id,
            'admin_name'          => $application->admin_name,
            'admin_email'         => $application->admin_email,
            'admin_password'      => 'password', // Default temporary password
            'subscription_due_at' => $subscriptionDueAt,
        ]);

        $application->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Application approved. Approval email sent to {$application->admin_email}");
    }

    public function reject(TenantApplication $application): RedirectResponse
    {
        if ($application->status !== 'pending') {
            return back()->with('error', 'Only pending applications can be rejected.');
        }

        $application->update([
            'status'      => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Application has been rejected.');
    }

    public function verifyPayment(TenantApplication $application): RedirectResponse
    {
        if ($application->status !== 'pending') {
            return back()->with('error', 'Only pending applications can have their payment verified.');
        }

        if ($application->payment_status === 'verified') {
            return back()->with('success', 'Payment has already been verified for this application.');
        }

        // If this application has a PayMongo link, verify via API first
        if ($application->paymongo_link_id) {
            $response = Http::withBasicAuth(config('paymongo.secret_key'), '')
                ->get('https://api.paymongo.com/v1/links/' . $application->paymongo_link_id);

            if ($response->successful()) {
                $data     = $response->json();
                $status   = $data['data']['attributes']['status'];
                $payments = $data['data']['attributes']['payments'] ?? [];

                if ($status === 'paid' && count($payments) > 0) {
                    $payment = $payments[0];

                    $application->update([
                        'payment_status'      => 'verified',
                        'paymongo_payment_id' => $payment['id'],
                        'paid_at'             => $application->paid_at ?? now(),
                        'payment_method'      => $application->payment_method ?? ($payment['attributes']['source']['type'] ?? null),
                        'payment_verified_by' => auth()->id(),
                        'payment_verified_at' => now(),
                    ]);

                    return back()->with('success', 'Payment verified via PayMongo API. You can now approve this application.');
                }

                return back()->with('error', 'PayMongo payment link exists but payment has not been completed yet.');
            }
        }

        // Manual verification (proof of payment / non-PayMongo route)
        $application->update([
            'payment_status'      => 'verified',
            'payment_verified_by' => auth()->id(),
            'payment_verified_at' => now(),
        ]);

        $application->refresh()->loadMissing('plan', 'paymentVerifier');

        try {
            Mail::to($application->admin_email)->send(new ApplicationPaymentVerifiedMail($application));

            return back()->with('success', 'Payment verified and confirmation email sent. You can now approve this application.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('warning', 'Payment verified, but the confirmation email could not be sent.');
        }
    }

    public function rejectPayment(TenantApplication $application): RedirectResponse
    {
        if ($application->status !== 'pending') {
            return back()->with('error', 'Only pending applications can have their payment review updated.');
        }

        $application->update([
            'payment_status'      => 'rejected',
            'payment_verified_by' => null,
            'payment_verified_at' => null,
        ]);

        return back()->with('success', 'Payment marked as rejected. The application remains pending until a valid payment is confirmed.');
    }

    public function paymentProof(TenantApplication $application): BinaryFileResponse
    {
        abort_if(blank($application->payment_proof_path), 404);
        abort_unless(Storage::exists((string) $application->payment_proof_path), 404);

        return response()->file(Storage::path((string) $application->payment_proof_path));
    }

    /**
     * Send (or create + send) a PayMongo payment link to the applicant.
     */
    public function sendPaymentLink(TenantApplication $application): RedirectResponse
    {
        if ($application->payment_status === 'verified') {
            return back()->with('info', 'This application has already been paid. No payment link needed.');
        }

        // Ensure we have a plan with a price
        $plan = $application->plan;
        if (! $plan || (float) $plan->price === 0.0) {
            return back()->with('info', 'This is a free plan. No payment link is required.');
        }

        // If no existing payment URL, create a new PayMongo link
        if (blank($application->payment_url)) {
            $amount = (int) round($plan->price * 100);

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
                return back()->with('error', 'Could not generate a PayMongo payment link. Please try again.');
            }

            $data = $response->json();

            $application->update([
                'paymongo_link_id' => $data['data']['id'],
                'payment_url'      => $data['data']['attributes']['checkout_url'],
                'amount_paid'      => $plan->price,
            ]);

            $application->refresh();
        }

        // Send the payment link via email
        try {
            Mail::to($application->admin_email)->send(new PaymentLinkMail($application));

            return back()->with('success', "Payment link sent to {$application->admin_email}.");
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('warning', 'Payment link created but the email could not be sent. Copy the URL manually from the application details.');
        }
    }
}
