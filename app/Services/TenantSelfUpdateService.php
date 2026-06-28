<?php

declare(strict_types=1);

namespace App\Services;

class TenantSelfUpdateService
{
    /**
     * Tenant self-update has been removed.
     * This service is kept as a shell to satisfy existing references but
     * always throws when called. The internal update pipeline (backup,
     * maintenance-mode, code-deployment, migrations, version-commit)
     * has been removed.
     */
    public function applyUpdate(string $tenantId, int $releaseId): never
    {
        throw new \RuntimeException('Tenant self-update functionality is no longer available.');
    }
}
