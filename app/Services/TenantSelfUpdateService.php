<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppRelease;
use App\Models\TenantSetting;
use App\Models\TenantUpdate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class TenantSelfUpdateService
{
    public function __construct(
        private TenantUpdateService $tenantUpdateService,
        private TenantBackupService $backupService,
        private TenantMigrationService $migrationService,
        private CodeDeploymentService $codeDeploymentService,
    ) {}

    /**
     * Apply an update to a specific tenant.
     *
     * Flow:
     *   1. Validate & prepare
     *   2. Create backup (database + files)
     *   3. Enter tenant-scoped maintenance mode
     *   4. Code deployment (if enabled)
     *   5. Run database migrations
     *   6. Commit version record
     *   7. Exit maintenance mode
     *
     * On failure at any step: rollback → restore backup → exit maintenance → log error.
     *
     * @return array{success: bool, release: AppRelease|null, error: string|null, details: array<string, mixed>}
     */
    public function applyUpdate(string $tenantId, int $releaseId): array
    {
        $release = AppRelease::findOrFail($releaseId);
        $tenant = $this->resolveTenant($tenantId);
        $currentVersion = $this->tenantUpdateService->getCurrentRelease($tenantId);
        $currentVersionTag = $currentVersion?->appRelease?->tag ?? 'v0.0.0';
        $backupPath = null;

        $details = [
            'tenant_id' => $tenantId,
            'from_version' => $currentVersionTag,
            'to_version' => $release->tag,
            'backup_path' => null,
            'migrations_run' => 0,
            'code_deployed' => false,
            'stages_completed' => [],
        ];

        Log::info('Starting tenant update', [
            'tenant_id' => $tenantId,
            'release_id' => $releaseId,
            'release_tag' => $release->tag,
            'from_version' => $currentVersionTag,
        ]);

        try {
            // ─── STEP 1: CREATE BACKUP ─────────────────────────────────────
            $backupResult = $this->backupService->createBackup($tenant, "pre_update_{$release->tag}");

            if (!$backupResult['success']) {
                $this->tenantUpdateService->markAsFailed(
                    $tenantId,
                    $releaseId,
                    'Backup failed: ' . ($backupResult['error'] ?? 'Unknown')
                );

                return [
                    'success' => false,
                    'release' => $release,
                    'error' => 'Pre-update backup failed: ' . ($backupResult['error'] ?? 'Unknown'),
                    'details' => $details,
                ];
            }

            $backupPath = $backupResult['backup_path'];
            $details['backup_path'] = $backupPath;
            $details['stages_completed'][] = 'backup';

            Log::info('Pre-update backup created', [
                'tenant_id' => $tenantId,
                'backup_path' => $backupPath,
            ]);

            // ─── STEP 2: ENTER MAINTENANCE MODE ────────────────────────────
            $this->enterMaintenanceMode($tenantId, $release->tag);
            $details['stages_completed'][] = 'maintenance_entered';

            // ─── STEP 3: CODE DEPLOYMENT (opt-in) ──────────────────────────
            if ($this->codeDeploymentService->isCodeDeploymentEnabled()) {
                $deployResult = $this->codeDeploymentService->deploy($release->tag);

                if (!$deployResult['success']) {
                    throw new \RuntimeException(
                        'Code deployment failed: ' . ($deployResult['error'] ?? 'Unknown')
                    );
                }

                $details['code_deployed'] = true;
                $details['stages_completed'][] = 'code_deployed';

                Log::info('Code deployment completed', [
                    'tenant_id' => $tenantId,
                    'version' => $release->tag,
                ]);
            }

            // ─── STEP 4: RUN DATABASE MIGRATIONS ───────────────────────────
            $migrationResult = $this->migrationService->runMigrationsForVersion(
                $tenant,
                $currentVersionTag,
                $release->tag
            );

            if (!$migrationResult['success']) {
                throw new \RuntimeException(
                    'Migration failed: ' . ($migrationResult['error'] ?? 'Unknown')
                );
            }

            $details['migrations_run'] = $migrationResult['migrations_run'];
            $details['stages_completed'][] = 'migrations_run';

            Log::info('Tenant migrations completed', [
                'tenant_id' => $tenantId,
                'migrations_run' => $migrationResult['migrations_run'],
            ]);

            // ─── STEP 5: COMMIT VERSION RECORD ─────────────────────────────
            $this->commitVersionRecord($tenantId, $release);
            $details['stages_completed'][] = 'version_committed';

            // ─── STEP 6: EXIT MAINTENANCE MODE ─────────────────────────────
            $this->exitMaintenanceMode($tenantId);
            $details['stages_completed'][] = 'maintenance_exited';

            Log::info('Tenant update completed successfully', [
                'tenant_id' => $tenantId,
                'release_tag' => $release->tag,
                'migrations_run' => $details['migrations_run'],
                'code_deployed' => $details['code_deployed'],
            ]);

            return [
                'success' => true,
                'release' => $release,
                'error' => null,
                'details' => $details,
            ];

        } catch (\Throwable $e) {
            Log::error('Tenant update failed — initiating rollback', [
                'tenant_id' => $tenantId,
                'release_id' => $releaseId,
                'error' => $e->getMessage(),
                'stages_completed' => $details['stages_completed'],
            ]);

            // ─── ROLLBACK: RESTORE BACKUP ──────────────────────────────────
            if ($backupPath !== null) {
                try {
                    $restoreResult = $this->backupService->restoreBackup($tenant, $backupPath);

                    if ($restoreResult['success']) {
                        Log::info('Tenant backup restored after failed update', [
                            'tenant_id' => $tenantId,
                            'backup_path' => $backupPath,
                        ]);
                    } else {
                        Log::error('Tenant backup restore also failed', [
                            'tenant_id' => $tenantId,
                            'restore_error' => $restoreResult['error'],
                        ]);
                    }
                } catch (\Throwable $restoreException) {
                    Log::error('Tenant backup restore threw an exception', [
                        'tenant_id' => $tenantId,
                        'restore_error' => $restoreException->getMessage(),
                    ]);
                }
            }

            // ─── ALWAYS EXIT MAINTENANCE MODE ──────────────────────────────
            $this->exitMaintenanceMode($tenantId);

            // ─── MARK AS FAILED ────────────────────────────────────────────
            $this->tenantUpdateService->markAsFailed($tenantId, $releaseId, $e->getMessage());

            return [
                'success' => false,
                'release' => $release,
                'error' => $e->getMessage(),
                'details' => $details,
            ];
        }
    }

    /**
     * Commit the version record in the central database.
     * Also clears the update_required flag on the central Tenant so the
     * blocking modal stops showing after the update is applied.
     */
    protected function commitVersionRecord(string $tenantId, AppRelease $release): void
    {
        $this->tenantUpdateService->markAsUpdated($tenantId, $release->id, [
            'updated_by' => auth()->id(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $tenant = $this->resolveTenant($tenantId);
        $updatedBy = auth()->user()?->email ?? auth()->user()?->name ?? 'system';

        // Clear the central update_required flag so the blocking modal is dismissed.
        if ($tenant->update_required) {
            $requiredVersion = (string) ($tenant->update_required_version ?? '');

            // Only clear if the tenant has now met or exceeded the required version.
            $meetsRequirement = $requiredVersion === ''
                || version_compare(
                    ltrim($release->tag, 'vV'),
                    ltrim($requiredVersion, 'vV'),
                    '>='
                );

            if ($meetsRequirement) {
                $tenant->update_required         = false;
                $tenant->update_required_version = null;
                $tenant->save();

                Log::info('Cleared update_required flag on tenant after successful update', [
                    'tenant_id'        => $tenantId,
                    'updated_to'       => $release->tag,
                    'was_required'     => $requiredVersion,
                ]);
            }
        }

        $tenant->run(static function () use ($release, $updatedBy): void {
            TenantSetting::set('current_version', $release->tag);
            TenantSetting::set('last_updated_at', now()->toIso8601String());
            TenantSetting::set('last_updated_by', (string) $updatedBy);
        });
    }

    /**
     * Enter tenant-scoped maintenance mode.
     */
    protected function enterMaintenanceMode(string $tenantId, string $versionTag): void
    {
        if (!config('updates.tenant_maintenance.enabled', true)) {
            return;
        }

        $ttlMinutes = (int) config('updates.tenant_maintenance.ttl_minutes', 60);
        $cacheStore = (string) config('updates.tenant_maintenance.cache_store', 'file');

        Cache::store($cacheStore)->put(
            $this->maintenanceCacheKey($tenantId),
            [
                'entered_at' => now()->toIso8601String(),
                'version' => $versionTag,
                'reason' => 'Applying update',
            ],
            now()->addMinutes($ttlMinutes)
        );

        Log::info('Tenant entered maintenance mode', [
            'tenant_id' => $tenantId,
            'ttl_minutes' => $ttlMinutes,
        ]);
    }

    /**
     * Exit tenant-scoped maintenance mode.
     */
    protected function exitMaintenanceMode(string $tenantId): void
    {
        $cacheStore = (string) config('updates.tenant_maintenance.cache_store', 'file');

        try {
            Cache::store($cacheStore)->forget($this->maintenanceCacheKey($tenantId));
        } catch (\Throwable $e) {
            Log::warning('Failed to clear maintenance mode cache', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Tenant exited maintenance mode', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Check if a tenant is currently in maintenance mode.
     */
    public function isInMaintenanceMode(string $tenantId): bool
    {
        $cacheStore = (string) config('updates.tenant_maintenance.cache_store', 'file');

        try {
            return Cache::store($cacheStore)->has($this->maintenanceCacheKey($tenantId));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get maintenance mode info for a tenant.
     *
     * @return array<string, mixed>|null
     */
    public function getMaintenanceInfo(string $tenantId): ?array
    {
        $cacheStore = (string) config('updates.tenant_maintenance.cache_store', 'file');

        try {
            $data = Cache::store($cacheStore)->get($this->maintenanceCacheKey($tenantId));
            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Cache key for tenant maintenance mode.
     */
    protected function maintenanceCacheKey(string $tenantId): string
    {
        return "tenant:update:maintenance:{$tenantId}";
    }

    /**
     * Resolve the tenant model from the ID.
     */
    protected function resolveTenant(string $tenantId): TenantWithDatabase
    {
        $tenantModel = config('tenancy.tenant_model');

        /** @var TenantWithDatabase $tenant */
        $tenant = $tenantModel::findOrFail($tenantId);

        return $tenant;
    }
}
