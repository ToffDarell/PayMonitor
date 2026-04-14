<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\GitHubVersionService;
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

        return view('central.versions.index', [
            'updateInfo' => $updateInfo,
            'changelogItems' => $changelogItems,
            'updateHistory' => $updateHistory,
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
}
