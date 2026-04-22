<?php

namespace App\Console\Commands;

use App\Models\TenantUpdate;
use App\Services\TenantUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTenantUpdatesCommand extends Command
{
    protected $signature = 'tenants:backfill-updates {--tenant= : Specific tenant ID} {--dry-run : Preview without making changes}';
    protected $description = 'Backfill tenant_updates for existing tenants without current release';

    public function handle(TenantUpdateService $service): int
    {
        $tenants = $this->getTenants();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found to backfill');
            return self::SUCCESS;
        }

        $this->info("Found {$tenants->count()} tenant(s) to process");

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                $existing = TenantUpdate::forTenant($tenant->id)->current()->exists();

                if ($existing) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if (!$this->option('dry-run')) {
                    $service->initializeTenantRelease($tenant->id);
                }

                $processed++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed for tenant {$tenant->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Processed: {$processed}");
        $this->info("→ Skipped: {$skipped}");
        
        if ($failed > 0) {
            $this->error("✗ Failed: {$failed}");
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No changes made');
        }

        return self::SUCCESS;
    }

    private function getTenants()
    {
        $query = DB::table('tenants');

        if ($tenantId = $this->option('tenant')) {
            $query->where('id', $tenantId);
        }

        return $query->get();
    }
}
