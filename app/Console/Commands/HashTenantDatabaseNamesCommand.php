<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class HashTenantDatabaseNamesCommand extends Command
{
    protected $signature = 'tenants:hash-database-names
        {--tenant=* : Specific tenant IDs to migrate}
        {--force : Run without confirmation}';

    protected $description = 'Rename existing tenant databases to hashed names and persist the resolved db_name.';

    public function handle(): int
    {
        $tenantIds = collect((array) $this->option('tenant'))
            ->filter(static fn (mixed $value): bool => filled($value))
            ->map(static fn (mixed $value): string => (string) $value)
            ->values();

        $tenants = Tenant::query()
            ->when($tenantIds->isNotEmpty(), static fn ($query) => $query->whereIn('id', $tenantIds->all()))
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found for database hashing.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Rename databases for {$tenants->count()} tenant(s)?", false)) {
            $this->warn('Operation cancelled.');

            return self::INVALID;
        }

        $renamed = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            try {
                $result = $this->migrateTenantDatabaseName($tenant);

                match ($result) {
                    'renamed' => $renamed++,
                    'updated' => $updated++,
                    default => $skipped++,
                };
            } catch (Throwable $throwable) {
                $this->error("Failed for tenant [{$tenant->id}]: {$throwable->getMessage()}");

                return self::FAILURE;
            }
        }

        $this->info("Completed. Renamed: {$renamed}, Updated only: {$updated}, Skipped: {$skipped}.");

        return self::SUCCESS;
    }

    protected function migrateTenantDatabaseName(Tenant $tenant): string
    {
        $manager = $tenant->database()->manager();
        $hashedName = $tenant->hashedDatabaseName();
        $legacyName = $tenant->legacyDatabaseName();
        $storedName = $tenant->getInternal('db_name');

        if ($manager->databaseExists($hashedName)) {
            if ($storedName !== $hashedName) {
                $tenant->setInternal('db_name', $hashedName);
                $tenant->save();
                $this->line("Updated stored db_name for tenant [{$tenant->id}] to hashed name.");

                return 'updated';
            }

            $this->line("Skipped tenant [{$tenant->id}] - hashed database already in use.");

            return 'skipped';
        }

        if (! $manager->databaseExists($legacyName)) {
            $this->line("Skipped tenant [{$tenant->id}] - no legacy database found.");

            return 'skipped';
        }

        $driver = config('database.connections.'.$tenant->database()->getTemplateConnectionName().'.driver');

        if ($driver === 'mysql') {
            $this->renameMysqlDatabase($tenant, $legacyName, $hashedName);
        } elseif ($driver === 'sqlite') {
            $this->renameSqliteDatabase($legacyName, $hashedName);
        } else {
            throw new \RuntimeException("Database hashing migration is not implemented for driver [{$driver}].");
        }

        $tenant->setInternal('db_name', $hashedName);
        $tenant->save();

        $this->info("Renamed tenant [{$tenant->id}] database: {$legacyName} -> {$hashedName}");

        return 'renamed';
    }

    protected function renameMysqlDatabase(Tenant $tenant, string $legacyName, string $hashedName): void
    {
        $connectionName = (string) config('tenancy.database.central_connection', config('database.default'));
        $connection = DB::connection($connectionName);
        $charset = (string) ($connection->getConfig('charset') ?? 'utf8mb4');
        $collation = (string) ($connection->getConfig('collation') ?? 'utf8mb4_unicode_ci');

        $connection->statement("CREATE DATABASE `{$hashedName}` CHARACTER SET `{$charset}` COLLATE `{$collation}`");

        $tables = collect($connection->select(
            'SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_TYPE = ?',
            [$legacyName, 'BASE TABLE']
        ))->pluck('TABLE_NAME')->map(static fn (mixed $name): string => (string) $name)->values();

        if ($tables->isNotEmpty()) {
            $renameClauses = $tables
                ->map(fn (string $table): string => sprintf(
                    '`%s`.`%s` TO `%s`.`%s`',
                    $legacyName,
                    $table,
                    $hashedName,
                    $table,
                ))
                ->implode(', ');

            $connection->statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                $connection->statement("RENAME TABLE {$renameClauses}");
            } finally {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        $remainingTables = collect($connection->select(
            'SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_TYPE = ?',
            [$legacyName, 'BASE TABLE']
        ));

        if ($remainingTables->isNotEmpty()) {
            throw new \RuntimeException("Legacy database [{$legacyName}] still contains tables after rename.");
        }

        $connection->statement("DROP DATABASE `{$legacyName}`");
    }

    protected function renameSqliteDatabase(string $legacyName, string $hashedName): void
    {
        $legacyPath = database_path($legacyName);
        $hashedPath = database_path($hashedName);

        if (! file_exists($legacyPath)) {
            throw new \RuntimeException("SQLite database [{$legacyPath}] was not found.");
        }

        if (file_exists($hashedPath)) {
            throw new \RuntimeException("Target SQLite database [{$hashedPath}] already exists.");
        }

        if (! rename($legacyPath, $hashedPath)) {
            throw new \RuntimeException("Unable to rename SQLite database [{$legacyPath}] to [{$hashedPath}].");
        }
    }
}
