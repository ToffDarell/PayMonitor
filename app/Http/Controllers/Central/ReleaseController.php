<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\AdminReleaseService;
use App\Services\ReleaseRegistryService;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function __construct(
        private ReleaseRegistryService $registryService,
        private AdminReleaseService $adminService
    ) {}

    public function index()
    {
        $releases = AppRelease::latest('published_at')->paginate(20);
        $statistics = $this->adminService->getUpdateStatistics();

        return view('central.releases.index', compact('releases', 'statistics'));
    }

    public function sync()
    {
        $result = $this->registryService->syncFromGitHub();

        if ($result['success']) {
            return back()->with('success', "Synced {$result['synced']} releases, skipped {$result['skipped']}, notified {$result['notified']} tenant(s)");
        }

        return back()->with('error', "Sync failed: {$result['error']}");
    }

    public function markRequired(Request $request, AppRelease $release)
    {
        $request->validate([
            'grace_days' => 'nullable|integer|min:0|max:90',
        ]);

        $gracePeriod = $request->input('grace_days') 
            ? now()->addDays($request->input('grace_days'))
            : now()->addDays(7);

        $this->adminService->markAsRequired($release->id, $gracePeriod);

        return back()->with('success', 'Release marked as required');
    }

    public function unmarkRequired(AppRelease $release)
    {
        $this->adminService->unmarkAsRequired($release->id);

        return back()->with('success', 'Release unmarked as required');
    }

    public function notifyAll(AppRelease $release)
    {
        $count = $this->adminService->notifyAllTenantsOfUpdate($release->id);

        return back()->with('success', "Notified {$count} tenants");
    }

    public function forceMarkAll(Request $request, AppRelease $release)
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $count = $this->adminService->forceMarkAllAsUpdated($release->id);

        return back()->with('warning', "Force-marked {$count} tenants as updated");
    }

    public function tenantsNeedingUpdate(AppRelease $release)
    {
        $tenants = $this->adminService->getTenantsNeedingUpdate($release->id);

        return view('central.releases.tenants-needing-update', compact('release', 'tenants'));
    }
}
