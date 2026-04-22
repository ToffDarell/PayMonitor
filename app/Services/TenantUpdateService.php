<?php

namespace App\Services;

use App\Models\AppRelease;
use App\Models\TenantUpdate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantUpdateService
{
    public function initializeTenantRelease(string $tenantId, ?int $releaseId = null): TenantUpdate
    {
        $release = $releaseId
            ? AppRelease::findOrFail($releaseId)
            : AppRelease::stable()->latest('published_at')->first();

        if (! $release) {
            throw new \Exception('No stable release available to initialize tenant');
        }

        return DB::transaction(function () use ($tenantId, $release): TenantUpdate {
            TenantUpdate::forTenant($tenantId)->update(['is_current' => false]);

            return TenantUpdate::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'app_release_id' => $release->id,
                ],
                [
                    'status' => TenantUpdate::STATUS_UPDATED,
                    'is_current' => true,
                    'applied_at' => now(),
                    'required_at' => null,
                    'grace_until' => null,
                    'failure_reason' => null,
                ]
            );
        });
    }

    public function getCurrentRelease(string $tenantId): ?TenantUpdate
    {
        return TenantUpdate::forTenant($tenantId)
            ->current()
            ->with('appRelease')
            ->first();
    }

    public function getAvailableUpdates(string $tenantId): array
    {
        $current = $this->getCurrentRelease($tenantId);

        $stableReleases = AppRelease::query()
            ->stable()
            ->latest('published_at')
            ->get();

        $stableReleases = $stableReleases
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
            ->values();

        if (! $current || ! $current->relationLoaded('appRelease') || $current->appRelease === null) {
            return $stableReleases->toArray();
        }

        return $stableReleases
            ->filter(fn (AppRelease $release): bool => $this->isReleaseNewerThan($release->tag, $current->appRelease->tag))
            ->values()
            ->toArray();
    }

    public function markUpdateAvailable(string $tenantId, int $releaseId): TenantUpdate
    {
        return TenantUpdate::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'app_release_id' => $releaseId,
            ],
            [
                'status' => TenantUpdate::STATUS_UPDATE_AVAILABLE,
                'is_current' => false,
            ]
        );
    }

    public function syncAvailabilityForTenant(string $tenantId, AppRelease $latestStableRelease): bool
    {
        if ($this->isTenantOnReleaseOrNewer($tenantId, $latestStableRelease)) {
            return false;
        }

        $this->markUpdateAvailable($tenantId, $latestStableRelease->id);

        return true;
    }

    public function isTenantOnReleaseOrNewer(string $tenantId, AppRelease $release): bool
    {
        $current = $this->getCurrentRelease($tenantId);

        if (! $current || $current->appRelease === null) {
            return false;
        }

        $currentVersion = $this->normalizeVersion($current->appRelease->tag);
        $targetVersion = $this->normalizeVersion($release->tag);

        return version_compare($currentVersion, $targetVersion, '>=');
    }

    public function markAsUpdated(string $tenantId, int $releaseId, array $metadata = []): TenantUpdate
    {
        return DB::transaction(function () use ($tenantId, $releaseId, $metadata) {
            TenantUpdate::forTenant($tenantId)->update(['is_current' => false]);

            $update = TenantUpdate::updateOrCreate(
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
                    'metadata' => $metadata,
                ]
            );

            Log::info('Tenant updated to new release', [
                'tenant_id' => $tenantId,
                'release_id' => $releaseId,
                'metadata' => $metadata,
            ]);

            return $update;
        });
    }

    public function markAsFailed(string $tenantId, int $releaseId, string $reason): TenantUpdate
    {
        $update = TenantUpdate::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'app_release_id' => $releaseId,
            ],
            [
                'status' => TenantUpdate::STATUS_FAILED,
                'is_current' => false,
                'failure_reason' => $reason,
            ]
        );

        Log::error('Tenant update failed', [
            'tenant_id' => $tenantId,
            'release_id' => $releaseId,
            'reason' => $reason,
        ]);

        return $update;
    }



    public function isUpdateRequired(string $tenantId): bool
    {
        return TenantUpdate::forTenant($tenantId)
            ->requiredAndOverdue()
            ->exists();
    }

    public function getRequiredUpdate(string $tenantId): ?TenantUpdate
    {
        return TenantUpdate::forTenant($tenantId)
            ->requiredAndOverdue()
            ->with('appRelease')
            ->first();
    }

    private function isReleaseNewerThan(string $candidateTag, string $currentTag): bool
    {
        return version_compare(
            $this->normalizeVersion($candidateTag),
            $this->normalizeVersion($currentTag),
            '>'
        );
    }

    private function normalizeVersion(string $tag): string
    {
        $normalized = ltrim(trim($tag), 'vV');

        return $normalized === '' ? '0' : $normalized;
    }
}
