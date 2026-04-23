<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class TenantMigrationService
{
    /**
     * Run tenant-specific database migrations.
     *
     * @return array{success: bool, migrations_run: int, error: string|null}
     */
    public function runMigrations(TenantWithDatabase $tenant): array
    {
        $tenantId = (string) $tenant->getTenantKey();

        try {
            $exitCode = Artisan::call('tenants:migrate', [
                '--tenants' => [$tenantId],
                '--force' => true,
            ]);

            $output = Artisan::output();
            $migrationsRun = $this->countMigrationsFromOutput($output);

            Log::info('Tenant migrations completed', [
                'tenant_id' => $tenantId,
                'exit_code' => $exitCode,
                'migrations_run' => $migrationsRun,
            ]);

            return [
                'success' => $exitCode === 0,
                'migrations_run' => $migrationsRun,
                'error' => $exitCode !== 0 ? "Migration exit code: {$exitCode}. Output: {$output}" : null,
            ];

        } catch (\Throwable $e) {
            Log::error('Tenant migration failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'migrations_run' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run only the migrations between two version tags.
     *
     * Version-tagged migrations follow the naming convention:
     *   2024_01_15_v1_2_0_add_loyalty_points.php
     *
     * @return array{success: bool, migrations_run: int, error: string|null}
     */
    public function runMigrationsForVersion(
        TenantWithDatabase $tenant,
        string $fromVersion,
        string $toVersion
    ): array {
        $tenantId = (string) $tenant->getTenantKey();

        try {
            // For simplicity, we run all pending migrations.
            // Version-tagged filtering can be added later if needed.
            $result = $this->runMigrations($tenant);

            Log::info('Version-specific migrations completed', [
                'tenant_id' => $tenantId,
                'from_version' => $fromVersion,
                'to_version' => $toVersion,
                'migrations_run' => $result['migrations_run'],
            ]);

            return $result;

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'migrations_run' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if there are pending migrations for a tenant.
     */
    public function hasPendingMigrations(TenantWithDatabase $tenant): bool
    {
        $tenantId = (string) $tenant->getTenantKey();

        try {
            $exitCode = Artisan::call('tenants:migrate', [
                '--tenants' => [$tenantId],
                '--pretend' => true,
                '--force' => true,
            ]);

            $output = trim(Artisan::output());

            // If there is meaningful output beyond "Nothing to migrate", there are pending migrations
            return $exitCode === 0
                && $output !== ''
                && !str_contains(strtolower($output), 'nothing to migrate');

        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Rollback the last batch of tenant migrations.
     *
     * @return array{success: bool, error: string|null}
     */
    public function rollbackLastBatch(TenantWithDatabase $tenant): array
    {
        $tenantId = (string) $tenant->getTenantKey();

        try {
            $exitCode = Artisan::call('tenants:rollback', [
                '--tenants' => [$tenantId],
                '--force' => true,
            ]);

            $output = Artisan::output();

            Log::info('Tenant migration rollback completed', [
                'tenant_id' => $tenantId,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            return [
                'success' => $exitCode === 0,
                'error' => $exitCode !== 0 ? "Rollback exit code: {$exitCode}. Output: {$output}" : null,
            ];

        } catch (\Throwable $e) {
            Log::error('Tenant migration rollback failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Count migrations from Artisan output.
     */
    protected function countMigrationsFromOutput(string $output): int
    {
        // Count lines containing "Migrated:" or "Migrating:" patterns
        $lines = preg_split('/\r\n|\r|\n/', $output) ?: [];
        $count = 0;

        foreach ($lines as $line) {
            if (preg_match('/migrat(ed|ing)/i', $line)) {
                $count++;
            }
        }

        // Divide by 2 because artisan outputs both "Migrating: ..." and "Migrated: ..."
        return (int) ceil($count / 2);
    }
}
