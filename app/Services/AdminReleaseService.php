<?php

namespace App\Services;

use App\Models\AppRelease;
use App\Models\TenantUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminReleaseService
{
    public function __construct(
        private TenantUpdateService $tenantUpdateService
    ) {}

    public function markAsRequired(int $releaseId, ?\DateTime $gracePeriod = null): void
    {
        $release = AppRelease::findOrFail($releaseId);

        $release->update(['is_required' => true]);

        $requiredAt = now();
        $graceUntil = $gracePeriod ?? now()->addDays(7);
        $targetRelease = $release;

        DB::transaction(function () use ($releaseId, $requiredAt, $graceUntil, $targetRelease): void {
            TenantUpdate::where('app_release_id', '!=', $releaseId)
                ->update([
                    'required_at' => null,
                    'grace_until' => null,
                ]);

            foreach (DB::table('tenants')->pluck('id') as $tenantId) {
                $tenantKey = (string) $tenantId;

                if ($this->tenantUpdateService->isTenantOnReleaseOrNewer($tenantKey, $targetRelease)) {
                    continue;
                }

                TenantUpdate::updateOrCreate(
                    [
                        'tenant_id' => $tenantKey,
                        'app_release_id' => $releaseId,
                    ],
                    [
                        'status' => TenantUpdate::STATUS_UPDATE_AVAILABLE,
                        'is_current' => false,
                        'required_at' => $requiredAt,
                        'grace_until' => $graceUntil,
                    ]
                );
            }
        });

        Log::info('Release marked as required', [
            'release_id' => $releaseId,
            'grace_until' => $graceUntil,
        ]);
    }

    public function unmarkAsRequired(int $releaseId): void
    {
        $release = AppRelease::findOrFail($releaseId);

        $release->update(['is_required' => false]);

        TenantUpdate::where('app_release_id', $releaseId)
            ->update([
                'required_at' => null,
                'grace_until' => null,
            ]);

        Log::info('Release unmarked as required', [
            'release_id' => $releaseId,
        ]);
    }

    public function notifyAllTenantsOfUpdate(int $releaseId): int
    {
        $release = AppRelease::findOrFail($releaseId);

        $tenants = DB::table('tenants')->pluck('id');
        $notified = 0;

        foreach ($tenants as $tenantId) {
            $tenantKey = (string) $tenantId;

            if ($this->tenantUpdateService->isTenantOnReleaseOrNewer($tenantKey, $release)) {
                continue;
            }

            $this->tenantUpdateService->markUpdateAvailable($tenantKey, $releaseId, true);
            $notified++;
        }

        Log::info('Tenants notified of new release', [
            'release_id' => $releaseId,
            'notified_count' => $notified,
        ]);

        return $notified;
    }

    public function forceMarkAllAsUpdated(int $releaseId): int
    {
        AppRelease::findOrFail($releaseId);

        $tenants = DB::table('tenants')->pluck('id');
        $updated = 0;

        DB::transaction(function () use ($tenants, $releaseId, &$updated) {
            foreach ($tenants as $tenantId) {
                TenantUpdate::forTenant($tenantId)->update(['is_current' => false]);

                TenantUpdate::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'app_release_id' => $releaseId,
                    ],
                    [
                        'status' => TenantUpdate::STATUS_UPDATED,
                        'is_current' => true,
                        'applied_at' => now(),
                        'required_at' => null,
                        'grace_until' => null,
                        'failure_reason' => null,
                        'metadata' => [
                            'force_marked_by' => auth()->id(),
                            'force_marked_at' => now()->toIso8601String(),
                        ],
                    ]
                );
                $updated++;
            }
        });

        Log::warning('All tenants force-marked as updated', [
            'release_id' => $releaseId,
            'tenant_count' => $updated,
            'admin_id' => auth()->id(),
        ]);

        return $updated;
    }

    public function getTenantsNeedingUpdate(int $releaseId): array
    {
        $targetRelease = AppRelease::findOrFail($releaseId);
        $tenants = DB::table('tenants')->get();
        $needingUpdate = [];

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->id;
            $current = TenantUpdate::forTenant($tenantId)
                ->current()
                ->with('appRelease')
                ->first();

            if (! $this->tenantUpdateService->isTenantOnReleaseOrNewer($tenantId, $targetRelease)) {
                $needingUpdate[] = [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->name ?? $tenant->id,
                    'current_release' => $current?->appRelease->tag ?? 'None',
                ];
            }
        }

        return $needingUpdate;
    }

    public function getUpdateStatistics(): array
    {
        $tenantIds = DB::table('tenants')->pluck('id');
        $totalTenants = $tenantIds->count();
        $latestRelease = $this->selectLatestStableRelease();

        if (! $latestRelease) {
            return [
                'total_tenants' => $totalTenants,
                'tracked_tenants' => 0,
                'up_to_date' => 0,
                'needs_update' => 0,
                'failed' => 0,
                'untracked' => $totalTenants,
                'latest_release' => null,
                'rollout_state' => $totalTenants > 0 ? 'tracking_incomplete' : 'healthy',
            ];
        }

        $upToDate = 0;
        $needsUpdate = 0;
        $failed = 0;
        $untracked = 0;

        foreach ($tenantIds as $tenantId) {
            $tenantKey = (string) $tenantId;
            $latestAttempt = TenantUpdate::forTenant($tenantKey)
                ->with('appRelease')
                ->latest('updated_at')
                ->latest('created_at')
                ->latest('id')
                ->first();
            $current = TenantUpdate::forTenant((string) $tenantId)
                ->current()
                ->with('appRelease')
                ->first();

            if ($latestAttempt?->status === TenantUpdate::STATUS_FAILED) {
                $failed++;
                continue;
            }

            if (! $current || $current->appRelease === null) {
                $untracked++;
                continue;
            }

            if ($current->status !== TenantUpdate::STATUS_UPDATED) {
                $needsUpdate++;
                continue;
            }

            if (version_compare($this->normalizeVersion($current->appRelease->tag), $this->normalizeVersion($latestRelease->tag), '>=')) {
                $upToDate++;
                continue;
            }

            $needsUpdate++;
        }

        $trackedTenants = max(0, $totalTenants - $untracked);

        return [
            'total_tenants' => $totalTenants,
            'tracked_tenants' => $trackedTenants,
            'up_to_date' => $upToDate,
            'needs_update' => $needsUpdate,
            'failed' => $failed,
            'untracked' => $untracked,
            'latest_release' => $latestRelease->tag,
            'rollout_state' => $untracked > 0
                ? 'tracking_incomplete'
                : (($needsUpdate > 0 || $failed > 0) ? 'needs_attention' : 'healthy'),
        ];
    }

    public function backfillMissingCurrentTracking(): array
    {
        $latestRelease = $this->selectLatestStableRelease();

        if (! $latestRelease) {
            return [
                'backfilled' => 0,
                'skipped' => 0,
                'latest_release' => null,
            ];
        }

        $backfilled = 0;
        $skipped = 0;

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            $tenantKey = (string) $tenantId;
            $hasCurrent = TenantUpdate::forTenant($tenantKey)->current()->exists();
            $hasHistory = TenantUpdate::forTenant($tenantKey)->exists();

            if ($hasCurrent || $hasHistory) {
                $skipped++;
                continue;
            }

            $this->tenantUpdateService->initializeTenantRelease($tenantKey, $latestRelease->id);
            $backfilled++;
        }

        Log::info('Backfilled missing tenant release tracking', [
            'release_id' => $latestRelease->id,
            'backfilled' => $backfilled,
            'skipped' => $skipped,
        ]);

        return [
            'backfilled' => $backfilled,
            'skipped' => $skipped,
            'latest_release' => $latestRelease->tag,
        ];
    }

    private function normalizeVersion(string $tag): string
    {
        $normalized = ltrim(trim($tag), 'vV');

        return $normalized === '' ? '0' : $normalized;
    }

    private function selectLatestStableRelease(): ?AppRelease
    {
        /** @var \Illuminate\Support\Collection<int, AppRelease> $stableReleases */
        $stableReleases = AppRelease::stable()->get();

        if ($stableReleases->isEmpty()) {
            return null;
        }

        return $stableReleases
            ->sort(function (AppRelease $left, AppRelease $right): int {
                $versionComparison = version_compare(
                    $this->normalizeVersion($left->tag),
                    $this->normalizeVersion($right->tag)
                );

                if ($versionComparison !== 0) {
                    return $versionComparison > 0 ? -1 : 1;
                }

                $leftPublishedAt = $left->published_at?->getTimestamp() ?? 0;
                $rightPublishedAt = $right->published_at?->getTimestamp() ?? 0;

                if ($leftPublishedAt !== $rightPublishedAt) {
                    return $leftPublishedAt > $rightPublishedAt ? -1 : 1;
                }

                return strcmp($right->tag, $left->tag);
            })
            ->first();
    }
}
