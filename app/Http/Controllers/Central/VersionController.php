<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\GitHubVersionService;
use App\Services\ReleaseRegistryService;
use App\Services\AdminReleaseService;
use App\Models\AppRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class VersionController extends Controller
{
    public function __construct()
    {
        $this->middleware(static function ($request, $next) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $versionService = app(GitHubVersionService::class);
        $updateInfo = $versionService->getUpdateInfo();
        $changelogItems = $versionService->parseChangelog((string) ($updateInfo['changelog'] ?? ''));
        $updateHistory = $versionService->getUpdateHistory();

        $releases = AppRelease::latest('published_at')->paginate(20);
        $statistics = app(AdminReleaseService::class)->getUpdateStatistics();

        return view('central.versions.index', [
            'updateInfo' => $updateInfo,
            'changelogItems' => $changelogItems,
            'updateHistory' => $updateHistory,
            'releases' => $releases,
            'statistics' => $statistics,
        ]);
    }

    public function checkForUpdates(): JsonResponse
    {
        Cache::forget('github_latest_release');
        Cache::forget('github_latest_release_info');

        $updateInfo = app(GitHubVersionService::class)->getUpdateInfo();

        return response()->json([
            'update_available' => $updateInfo['update_available'] ?? false,
            'latest_version' => $updateInfo['latest_version'] ?? 'Unknown',
            'current_version' => $updateInfo['current_version'] ?? 'Unknown',
            'release_name' => $updateInfo['release_name'] ?? 'Unable to check',
            'changelog' => $updateInfo['changelog'] ?? '',
        ]);
    }

    public function applyUpdate(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $result = app(GitHubVersionService::class)->applyUpdate((string) ($request->user()?->email ?? 'superadmin@paymonitor.com'));

        if ((bool) ($result['success'] ?? false)) {
            return redirect()
                ->route('central.versions.index')
                ->with('success', (string) ($result['message'] ?? 'Update applied successfully'));
        }

        return redirect()
            ->route('central.versions.index')
            ->with('error', (string) ($result['message'] ?? 'Update failed'))
            ->with('warning', trim((string) ($result['output'] ?? '')));
    }

    public function backfillTracking(): RedirectResponse
    {
        $result = app(AdminReleaseService::class)->backfillMissingCurrentTracking();

        if (($result['latest_release'] ?? null) === null) {
            return back()->with('warning', 'No stable release is available yet to backfill tenant tracking.');
        }

        if (($result['backfilled'] ?? 0) === 0) {
            return back()->with('warning', 'No empty tenant tracking records needed backfilling.');
        }

        return back()->with(
            'success',
            "Backfilled {$result['backfilled']} tenant(s) to {$result['latest_release']}"
        );
    }

    public function syncReleases(): RedirectResponse
    {
        $result = app(ReleaseRegistryService::class)->syncFromGitHub();

        if ($result['success']) {
            return back()->with(
                'success',
                "Synced {$result['synced']} releases, skipped {$result['skipped']}, notified {$result['notified']} tenant(s)"
            );
        }

        return back()->with('error', "Sync failed: {$result['error']}");
    }

    public function markRequired(Request $request, AppRelease $release): RedirectResponse
    {
        $request->validate([
            'grace_days' => 'nullable|integer|min:0|max:90',
        ]);

        $gracePeriod = $request->input('grace_days') 
            ? now()->addDays($request->input('grace_days'))
            : now()->addDays(7);

        app(AdminReleaseService::class)->markAsRequired($release->id, $gracePeriod);

        return back()->with('success', 'Release marked as required');
    }

    public function unmarkRequired(AppRelease $release): RedirectResponse
    {
        app(AdminReleaseService::class)->unmarkAsRequired($release->id);

        return back()->with('success', 'Release unmarked as required');
    }

    public function notifyAll(AppRelease $release): RedirectResponse
    {
        $count = app(AdminReleaseService::class)->notifyAllTenantsOfUpdate($release->id);

        return back()->with('success', "Notified {$count} tenants");
    }

    public function forceMarkAll(Request $request, AppRelease $release): RedirectResponse
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $count = app(AdminReleaseService::class)->forceMarkAllAsUpdated($release->id);

        return back()->with('warning', "Force-marked {$count} tenants as updated");
    }
}
