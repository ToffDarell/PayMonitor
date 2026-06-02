<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Database\Seeders\ModuleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackfillTenantModulesCommand extends Command
{
    protected $signature = 'tenants:backfill-modules {--tenant= : Specific tenant ID} {--dry-run : Preview only}';

    protected $description = 'Run module migration and seeding for existing tenant databases';

    public function handle(): int
    {
        $tenants = Tenant::query()
            ->when(
                filled($this->option('tenant')),
                fn ($query) => $query->whereKey((string) $this->option('tenant'))
            )
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$tenants->count()} tenant(s)...");

        $processed = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $this->line("Tenant: {$tenant->id}");

            try {
                if ($this->option('dry-run')) {
                    $this->comment('  Dry run: would run tenant migration and module seeder.');
                    $processed++;
                    continue;
                }

                $exitCode = Artisan::call('tenants:migrate', [
                    '--tenants' => [$tenant->id],
                    '--force' => true,
                ]);

                if ($exitCode !== 0) {
                    $this->error("  Migration failed for {$tenant->id}.");
                    $failed++;
                    continue;
                }

                $seededCount = $tenant->run(function (): int {
                    if (! Schema::hasTable('modules')) {
                        return -1;
                    }

                    $before = \App\Models\Module::query()->count();
                    (new ModuleSeeder())->run();
                    $after = \App\Models\Module::query()->count();

                    return $after - $before;
                });

                if ($seededCount === -1) {
                    $this->error("  Modules table is still missing for {$tenant->id}.");
                    $failed++;
                    continue;
                }

                $this->info("  Seeded {$seededCount} module row(s).");
                $processed++;
            } catch (Throwable $throwable) {
                $this->error("  Failed: {$throwable->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Processed: {$processed}");

        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
