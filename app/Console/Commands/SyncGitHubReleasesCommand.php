<?php

namespace App\Console\Commands;

use App\Services\ReleaseRegistryService;
use Illuminate\Console\Command;

class SyncGitHubReleasesCommand extends Command
{
    protected $signature = 'releases:sync {--force : Force sync even if recently synced}';
    protected $description = 'Sync GitHub releases to app_releases table';

    public function handle(ReleaseRegistryService $service): int
    {
        $this->info('Syncing GitHub releases...');

        $result = $service->syncFromGitHub();

        if ($result['success']) {
            $this->info("✓ Synced {$result['synced']} new releases");
            $this->info("→ Skipped {$result['skipped']} existing releases");
            $this->info("→ Marked {$result['notified']} tenant(s) with update availability");
            return self::SUCCESS;
        }

        $this->error("✗ Sync failed: {$result['error']}");
        return self::FAILURE;
    }
}
