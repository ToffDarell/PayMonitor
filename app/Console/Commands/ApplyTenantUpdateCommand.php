<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ApplyTenantUpdateCommand extends Command
{
    protected $signature = 'tenant:update {tenant_id} {release_id}';
    protected $description = 'Tenant self-update is disabled';

    public function handle(): int
    {
        $this->warn('Tenant self-update is disabled.');

        return self::SUCCESS;
    }
}
