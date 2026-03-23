<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\TenantApplication;
use App\Services\TenantService;
use Illuminate\Http\Request;

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
        $application->load('plan', 'reviewer');
        $pendingCount = TenantApplication::where('status', 'pending')->count();
        return view('central.applications.show', compact('application', 'pendingCount'));
    }

    public function approve(TenantApplication $application, TenantService $tenantService)
    {
        if ($application->status !== 'pending') {
            return back()->with('error', 'Only pending applications can be approved.');
        }

        // Setup domain name based on cooperative name
        $domainStr = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $application->cooperative_name));

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

        return back()->with('success', "Application approved. Credentials sent to {$application->admin_email}");
    }

    public function reject(TenantApplication $application)
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
}