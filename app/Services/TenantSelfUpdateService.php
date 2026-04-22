<?php

namespace App\Services;

use App\Models\AppRelease;
use App\Models\TenantUpdate;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantSelfUpdateService
{
    public function __construct(
        private TenantUpdateService $tenantUpdateService
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

            $this->runMigrations($tenantId);

            $this->tenantUpdateService->markAsUpdated($tenantId, $releaseId, [
                'updated_by' => auth()->id(),
                'updated_at' => now()->toIso8601String(),
            ]);

            Log::info('Tenant update completed successfully', [
                'tenant_id' => $tenantId,
                'release_id' => $releaseId,
            ]);

            return [
                'success' => true,
                'release' => $release,
            ];

        } catch (\Exception $e) {
            Log::error('Tenant update failed', [
                'tenant_id' => $tenantId,
                'release_id' => $releaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->tenantUpdateService->markAsFailed($tenantId, $releaseId, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
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
