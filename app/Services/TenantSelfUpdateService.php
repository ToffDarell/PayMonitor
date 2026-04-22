<?php

namespace App\Services;

use App\Models\AppRelease;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TenantSelfUpdateService
{
    public function __construct(
        private TenantUpdateService $tenantUpdateService,
        private GitHubVersionService $gitHubVersionService,
    ) {}

    public function applyUpdate(string $tenantId, int $releaseId): array
    {
        $release = AppRelease::findOrFail($releaseId);

        try {
            Log::info('Starting tenant update', [
                'tenant_id' => $tenantId,
                'release_id' => $releaseId,
                'release_tag' => $release->tag,
            ]);

            // Step 1: Download and deploy new code files from GitHub release archive
            $deployResult = $this->gitHubVersionService->applyUpdate(
                auth()->user()?->email ?? 'tenant@paymonitor.com'
            );

            if (! $deployResult['success']) {
                // If already up to date or same version, treat as ok and just record it
                $alreadyUpToDate = str_contains(
                    strtolower((string) ($deployResult['message'] ?? '')),
                    'already up to date'
                );

                if (! $alreadyUpToDate) {
                    Log::warning('Code deployment failed, proceeding with migration only', [
                        'tenant_id' => $tenantId,
                        'deploy_output' => $deployResult['output'] ?? '',
                    ]);
                }
            }

            Log::info('Code deployment complete', [
                'tenant_id' => $tenantId,
                'deploy_success' => $deployResult['success'],
                'deploy_version' => $deployResult['version'] ?? '',
            ]);

            // Step 2: Run tenant-specific DB migrations
            $this->runMigrations($tenantId);

            // Step 3: Record the update in the central DB
            $this->tenantUpdateService->markAsUpdated($tenantId, $releaseId, [
                'updated_by' => auth()->id(),
                'updated_at' => now()->toIso8601String(),
            ]);

            Log::info('Tenant update completed successfully', [
                'tenant_id' => $tenantId,
                'release_id' => $releaseId,
            ]);

            return [
                'success'        => true,
                'release'        => $release,
                'deploy_output'  => $deployResult['output'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Tenant update failed', [
                'tenant_id' => $tenantId,
                'release_id' => $releaseId,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            $this->tenantUpdateService->markAsFailed($tenantId, $releaseId, $e->getMessage());

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    private function runMigrations(string $tenantId): void
    {
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenantId],
        ]);

        Log::info('Migrations completed', [
            'tenant_id' => $tenantId,
        ]);
    }
}
