<?php

namespace App\Console\Commands;

use App\Services\TenantSelfUpdateService;
use Illuminate\Console\Command;

class ApplyTenantUpdateCommand extends Command
{
    protected $signature = 'tenant:update {tenant_id} {release_id}';
    protected $description = 'Apply an update to a specific tenant';

    public function handle(TenantSelfUpdateService $service): int
    {
        $tenantId = $this->argument('tenant_id');
        $releaseId = $this->argument('release_id');

        $this->info("Applying update to tenant {$tenantId}...");

        $result = $service->applyUpdate($tenantId, $releaseId);

        if ($result['success']) {
            $this->info("✓ Update applied successfully");
            return self::SUCCESS;
        }

        $this->error("✗ Update failed: {$result['error']}");
        return self::FAILURE;
    }
}
