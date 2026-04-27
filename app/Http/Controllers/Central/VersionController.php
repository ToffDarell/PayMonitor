<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Mail\TenantUpdateNotificationMail;
use App\Models\AppRelease;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\AdminReleaseService;
use App\Services\GitHubVersionService;
use App\Services\ReleaseRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class VersionController extends Controller
{
    public function __construct(
        private readonly GitHubVersionService $versionService,
    ) {
        $this->middleware(static function ($request, $next) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $updateInfo     = $this->versionService->getUpdateInfo();
        $changelogItems = $this->versionService->parseChangelog((string) ($updateInfo['changelog'] ?? ''));
        $updateHistory  = $this->versionService->getUpdateHistory();
        $releases       = AppRelease::latest('published_at')->paginate(20);
        $statistics     = app(AdminReleaseService::class)->getUpdateStatistics();
        $latestVersion  = (string) ($updateInfo['latest_version'] ?? 'v1.0.0');

        // Load all tenants with their version info from tenant DBs
        $tenants = Tenant::with(['plan', 'domains'])->get();

        foreach ($tenants as $tenant) {
            try {
                $tenant->current_version  = $tenant->run(fn () => TenantSetting::get('current_version', 'v1.0.0'));
                $tenant->last_updated_at  = $tenant->run(fn () => TenantSetting::get('last_updated_at', null));
                $tenant->last_updated_by  = $tenant->run(fn () => TenantSetting::get('last_updated_by', null));
            } catch (\Throwable) {
                $tenant->current_version = 'v1.0.0';
                $tenant->last_updated_at = null;
                $tenant->last_updated_by = null;
            }
        }

        return view('central.versions.index', [
            'updateInfo'     => $updateInfo,
            'changelogItems' => $changelogItems,
            'updateHistory'  => $updateHistory,
            'releases'       => $releases,
            'statistics'     => $statistics,
            'tenants'        => $tenants,
            'latestVersion'  => $latestVersion,
        ]);
    }

    public function checkForUpdates(): JsonResponse
    {
        Cache::store('file')->forget('github_latest_release');
        Cache::store('file')->forget('github_latest_release_info');

        $updateInfo = $this->versionService->getUpdateInfo();

        return response()->json([
            'update_available' => $updateInfo['update_available'] ?? false,
            'latest_version'   => $updateInfo['latest_version'] ?? 'v1.0.0',
            'current_version'  => $updateInfo['current_version'] ?? 'v1.0.0',
            'release_name'     => $updateInfo['release_name'] ?? 'Unable to check',
            'changelog'        => $updateInfo['changelog'] ?? '',
        ]);
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

    public function notifyTenant(Tenant $tenant): RedirectResponse
    {
        $updateInfo    = $this->versionService->getUpdateInfo();
        $latestVersion = (string) ($updateInfo['latest_version'] ?? 'v1.0.0');
        $changelog     = (string) ($updateInfo['changelog'] ?? '');
        $releaseName   = (string) ($updateInfo['release_name'] ?? 'New Release');

        $tenantAdminEmail = (string) ($tenant->email ?? '');
        $tenantAdminName  = (string) ($tenant->admin_name ?? $tenant->name ?? 'Admin');

        $domain = $tenant->domains->first()?->domain ?? '';
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';
        $loginUrl   = $domain ? "{$scheme}://{$domain}/login" : '';
        $updatesUrl = $domain ? "{$scheme}://{$domain}/settings/updates" : '';

        if (blank($tenantAdminEmail)) {
            return back()->with('error', "No email address found for {$tenant->name}.");
        }

        try {
            Mail::to($tenantAdminEmail)->send(new TenantUpdateNotificationMail(
                tenant: $tenant,
                adminName: $tenantAdminName,
                latestVersion: $latestVersion,
                releaseName: $releaseName,
                changelog: $changelog,
                loginUrl: $loginUrl,
                updatesUrl: $updatesUrl,
            ));
        } catch (\Throwable $e) {
            return back()->with('error', "Failed to send notification: {$e->getMessage()}");
        }

        return back()->with('success', "Update notification sent to {$tenantAdminEmail}");
    }

    public function toggleRequired(Tenant $tenant): RedirectResponse
    {
        if ($tenant->update_required) {
            $tenant->update_required         = false;
            $tenant->update_required_version = null;
            $tenant->save();

            return back()->with('success', "Update requirement removed for {$tenant->name}.");
        }

        $latestTrackedRelease = AppRelease::query()
            ->stable()
            ->orderByDesc('published_at')
            ->first();

        if ($latestTrackedRelease !== null) {
            $latestVersion = (string) $latestTrackedRelease->tag;
            $releaseName = (string) ($latestTrackedRelease->title ?: 'New Release');
            $changelog = (string) ($latestTrackedRelease->changelog ?? '');
        } else {
            $latestRelease = $this->versionService->getLatestRelease();
            $latestVersion = (string) ($latestRelease['version'] ?? 'v1.0.0');
            $releaseName = (string) ($latestRelease['name'] ?? 'New Release');
            $changelog = (string) ($latestRelease['changelog'] ?? '');
        }

        if (!preg_match('/^v?\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', trim($latestVersion))) {
            return back()->with('error', 'Unable to determine a valid required version. Please sync releases first.');
        }

        $tenant->update_required         = true;
        $tenant->update_required_version = $latestVersion;
        $tenant->save();

        // Also send notification email
        $tenantAdminEmail = (string) ($tenant->email ?? '');
        $tenantAdminName  = (string) ($tenant->admin_name ?? $tenant->name ?? 'Admin');
        $tenant->loadMissing('domains');
        $domain     = $tenant->domains->first()?->domain ?? '';
        $scheme     = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';
        $loginUrl   = $domain ? "{$scheme}://{$domain}/login" : '';
        $updatesUrl = $domain ? "{$scheme}://{$domain}/settings/updates" : '';

        if (filled($tenantAdminEmail)) {
            try {
                Mail::to($tenantAdminEmail)->send(new TenantUpdateNotificationMail(
                    tenant: $tenant,
                    adminName: $tenantAdminName,
                    latestVersion: $latestVersion,
                    releaseName: $releaseName,
                    changelog: $changelog,
                    loginUrl: $loginUrl,
                    updatesUrl: $updatesUrl,
                ));
            } catch (\Throwable) {
                // Mail failure doesn't block the save
            }
        }

        return back()->with('success', "Update marked as required for {$tenant->name}. Notification sent.");
    }
}
