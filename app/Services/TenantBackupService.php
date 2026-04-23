<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use ZipArchive;

class TenantBackupService
{
    /**
     * Create a full backup of the tenant's database and storage files.
     *
     * @return array{success: bool, backup_path: string|null, error: string|null}
     */
    public function createBackup(TenantWithDatabase $tenant, string $label = 'pre_update'): array
    {
        $tenantId = (string) $tenant->getTenantKey();
        $timestamp = now()->format('Ymd_His');
        $backupDir = storage_path(config('updates.backup.storage_path', 'backups/tenants') . "/{$tenantId}");
        $backupName = "{$timestamp}_{$label}";
        $workDir = "{$backupDir}/{$backupName}";

        try {
            File::ensureDirectoryExists($workDir);

            // 1. Backup the tenant database
            $dbResult = $this->backupDatabase($tenant, $workDir);

            if (!$dbResult['success']) {
                return [
                    'success' => false,
                    'backup_path' => null,
                    'error' => 'Database backup failed: ' . ($dbResult['error'] ?? 'Unknown error'),
                ];
            }

            // 2. Backup tenant storage files
            $this->backupStorageFiles($tenant, $workDir);

            // 3. Write metadata
            $metadata = [
                'tenant_id' => $tenantId,
                'label' => $label,
                'created_at' => now()->toIso8601String(),
                'database_file' => $dbResult['file'],
                'database_method' => $dbResult['method'],
            ];

            file_put_contents(
                "{$workDir}/metadata.json",
                json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
            );

            // 4. Compress to ZIP
            $zipPath = "{$backupDir}/{$backupName}.zip";
            $this->compressToZip($workDir, $zipPath);

            // 5. Cleanup the uncompressed work directory
            File::deleteDirectory($workDir);

            // 6. Cleanup old backups
            $this->cleanupOldBackups($backupDir);

            Log::info('Tenant backup created successfully', [
                'tenant_id' => $tenantId,
                'backup_path' => $zipPath,
                'method' => $dbResult['method'],
            ]);

            return [
                'success' => true,
                'backup_path' => $zipPath,
                'error' => null,
            ];

        } catch (\Throwable $e) {
            Log::error('Tenant backup creation failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            // Cleanup partial work directory on failure
            if (is_dir($workDir)) {
                File::deleteDirectory($workDir);
            }

            return [
                'success' => false,
                'backup_path' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore a tenant's database from a backup ZIP.
     *
     * @return array{success: bool, error: string|null}
     */
    public function restoreBackup(TenantWithDatabase $tenant, string $backupPath): array
    {
        $tenantId = (string) $tenant->getTenantKey();

        if (!is_file($backupPath)) {
            return [
                'success' => false,
                'error' => "Backup file not found: {$backupPath}",
            ];
        }

        $extractDir = storage_path('app/restore-temp/' . md5($backupPath . microtime(true)));

        try {
            File::ensureDirectoryExists($extractDir);

            // 1. Extract ZIP
            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) {
                throw new \RuntimeException('Failed to open backup ZIP file.');
            }
            $zip->extractTo($extractDir);
            $zip->close();

            // 2. Read metadata
            $metadataFile = "{$extractDir}/metadata.json";
            if (!is_file($metadataFile)) {
                throw new \RuntimeException('Backup metadata.json not found inside archive.');
            }

            $metadata = json_decode((string) file_get_contents($metadataFile), true);

            if (!is_array($metadata)) {
                throw new \RuntimeException('Invalid backup metadata.');
            }

            // 3. Restore database
            $databaseFile = "{$extractDir}/" . ($metadata['database_file'] ?? 'database.sql');

            if (!is_file($databaseFile)) {
                throw new \RuntimeException('Database backup file not found inside archive.');
            }

            $this->restoreDatabase($tenant, $databaseFile, $metadata['database_method'] ?? 'php');

            Log::info('Tenant backup restored successfully', [
                'tenant_id' => $tenantId,
                'backup_path' => $backupPath,
            ]);

            return [
                'success' => true,
                'error' => null,
            ];

        } catch (\Throwable $e) {
            Log::error('Tenant backup restoration failed', [
                'tenant_id' => $tenantId,
                'backup_path' => $backupPath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } finally {
            if (is_dir($extractDir)) {
                File::deleteDirectory($extractDir);
            }
        }
    }

    /**
     * Backup the tenant database using mysqldump (preferred) or PHP fallback.
     *
     * @return array{success: bool, file: string, method: string, error: string|null}
     */
    protected function backupDatabase(TenantWithDatabase $tenant, string $workDir): array
    {
        $tenantDbName = $tenant->database()->getName();
        $connection = config('tenancy.database.central_connection', config('database.default'));
        $dbConfig = config("database.connections.{$connection}");
        $driver = $dbConfig['driver'] ?? 'mysql';

        $dumpFile = "{$workDir}/database.sql";

        if ($driver === 'mysql') {
            // Try mysqldump first
            $mysqldumpResult = $this->tryMysqldump($tenantDbName, $dbConfig, $dumpFile);

            if ($mysqldumpResult['success']) {
                return [
                    'success' => true,
                    'file' => 'database.sql',
                    'method' => 'mysqldump',
                    'error' => null,
                ];
            }

            // Fallback to PHP-based dump
            Log::info('mysqldump not available, using PHP fallback', [
                'tenant_db' => $tenantDbName,
            ]);
        }

        return $this->phpDatabaseDump($tenantDbName, $dbConfig, $dumpFile);
    }

    /**
     * Try using mysqldump binary.
     *
     * @return array{success: bool, error: string|null}
     */
    protected function tryMysqldump(string $database, array $dbConfig, string $outputFile): array
    {
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = (string) ($dbConfig['port'] ?? '3306');
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';

        $command = [
            'mysqldump',
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
        ];

        if ($password !== '') {
            $command[] = "--password={$password}";
        }

        $command[] = '--single-transaction';
        $command[] = '--routines';
        $command[] = '--triggers';
        $command[] = $database;

        try {
            $result = Process::timeout(300)->run($command);

            if ($result->successful()) {
                file_put_contents($outputFile, $result->output());
                return ['success' => true, 'error' => null];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * PHP-based database dump fallback (table by table).
     *
     * @return array{success: bool, file: string, method: string, error: string|null}
     */
    protected function phpDatabaseDump(string $database, array $dbConfig, string $outputFile): array
    {
        try {
            $driver = $dbConfig['driver'] ?? 'mysql';
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? 3306;
            $username = $dbConfig['username'] ?? 'root';
            $password = $dbConfig['password'] ?? '';

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $output = "-- PayMonitor PHP Database Dump\n";
            $output .= "-- Database: {$database}\n";
            $output .= "-- Date: " . now()->toIso8601String() . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Get CREATE TABLE statement
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $output .= ($createStmt['Create Table'] ?? '') . ";\n\n";

                // Dump data
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $columnList = implode('`, `', $columns);

                    foreach (array_chunk($rows, 500) as $chunk) {
                        $values = [];
                        foreach ($chunk as $row) {
                            $escaped = array_map(
                                fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                                array_values($row)
                            );
                            $values[] = '(' . implode(', ', $escaped) . ')';
                        }
                        $output .= "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n" . implode(",\n", $values) . ";\n\n";
                    }
                }
            }

            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

            file_put_contents($outputFile, $output);

            return [
                'success' => true,
                'file' => 'database.sql',
                'method' => 'php',
                'error' => null,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'file' => 'database.sql',
                'method' => 'php',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore a database from an SQL dump file.
     */
    protected function restoreDatabase(TenantWithDatabase $tenant, string $sqlFile, string $method): void
    {
        $tenantDbName = $tenant->database()->getName();
        $connection = config('tenancy.database.central_connection', config('database.default'));
        $dbConfig = config("database.connections.{$connection}");

        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 3306;
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';
        $driver = $dbConfig['driver'] ?? 'mysql';

        $dsn = "{$driver}:host={$host};port={$port};dbname={$tenantDbName}";
        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $sql = (string) file_get_contents($sqlFile);

        $pdo->exec($sql);
    }

    /**
     * Backup tenant storage files.
     */
    protected function backupStorageFiles(TenantWithDatabase $tenant, string $workDir): void
    {
        $tenantId = (string) $tenant->getTenantKey();

        // Tenant storage paths (stancl/tenancy stores tenant files under tenant-suffixed paths)
        $possibleStoragePaths = [
            storage_path("app/public"),
            storage_path("tenant{$tenantId}/app/public"),
        ];

        $filesDir = "{$workDir}/files";
        File::ensureDirectoryExists($filesDir);

        foreach ($possibleStoragePaths as $storagePath) {
            if (is_dir($storagePath) && count(File::allFiles($storagePath)) > 0) {
                File::copyDirectory($storagePath, $filesDir);
                break; // Only copy from the first valid path
            }
        }
    }

    /**
     * Compress a directory into a ZIP file.
     */
    protected function compressToZip(string $sourceDir, string $zipPath): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is not available.');
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Failed to create ZIP archive at: {$zipPath}");
        }

        $files = File::allFiles($sourceDir);

        foreach ($files as $file) {
            $relativePath = ltrim(str_replace($sourceDir, '', $file->getRealPath()), DIRECTORY_SEPARATOR);
            $zip->addFile($file->getRealPath(), $relativePath);
        }

        $zip->close();
    }

    /**
     * Remove old backups beyond the retention limit.
     */
    protected function cleanupOldBackups(string $backupDir): void
    {
        $maxBackups = (int) config('updates.backup.max_backups', 10);

        $zipFiles = collect(File::files($backupDir))
            ->filter(fn ($file) => $file->getExtension() === 'zip')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        if ($zipFiles->count() <= $maxBackups) {
            return;
        }

        $toDelete = $zipFiles->slice($maxBackups);

        foreach ($toDelete as $file) {
            File::delete($file->getRealPath());
        }

        Log::info('Cleaned up old tenant backups', [
            'directory' => $backupDir,
            'deleted_count' => $toDelete->count(),
        ]);
    }
}
