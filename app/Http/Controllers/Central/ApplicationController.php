<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\TenantApplication;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            return back()->with('error', 'Verify the payment proof before approving this application.');
        }

        // Setup domain name based on cooperative name
        $domainStr = $application->domain;

        // Let's create the tenant
        $tenantService->createTenant([
            'name' => $application->cooperative_name,
            'domain' => $domainStr,
            'plan_id' => $application->plan_id,
            'admin_name' => $application->admin_name,
            'admin_email' => $application->admin_email,
            'admin_password' => 'password', // Default temporary password
        ]);

        $application->update([
            'status' => 'approved',
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
            'status' => 'rejected',
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

        $application->update([
            'payment_status' => 'verified',
            'payment_verified_by' => auth()->id(),
            'payment_verified_at' => now(),
        ]);

        return back()->with('success', 'Payment proof verified. You can now approve this application.');
    }

    public function rejectPayment(TenantApplication $application): RedirectResponse
    {
        if ($application->status !== 'pending') {
            return back()->with('error', 'Only pending applications can have their payment review updated.');
        }

        $application->update([
            'payment_status' => 'rejected',
            'payment_verified_by' => null,
            'payment_verified_at' => null,
        ]);

        return back()->with('success', 'Payment proof marked as rejected. The application remains pending until a valid payment is confirmed.');
    }

    public function paymentProof(TenantApplication $application): BinaryFileResponse
    {
        abort_if(blank($application->payment_proof_path), 404);
        abort_unless(Storage::exists((string) $application->payment_proof_path), 404);

        return response()->file(Storage::path((string) $application->payment_proof_path));
    }
}
