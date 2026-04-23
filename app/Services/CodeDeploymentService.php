<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CodeDeploymentService
{
    /**
     * Deploy code from a GitHub release archive.
     *
     * @return array{success: bool, output: string, error: string|null}
     */
    public function deploy(string $versionTag): array
    {
        $logs = [];

        if (!$this->isCodeDeploymentEnabled()) {
            return [
                'success' => false,
                'output' => 'Code deployment is disabled in config/updates.php',
                'error' => 'Code deployment is disabled.',
            ];
        }

        // Preflight checks
        $preflight = $this->preflightChecks();
        if (!$preflight['passed']) {
            return [
                'success' => false,
                'output' => implode("\n", $preflight['errors']),
                'error' => 'Preflight checks failed.',
            ];
        }

        $repo = $this->resolveGitHubRepository();
        if ($repo === null) {
            return [
                'success' => false,
                'output' => 'Unable to determine GitHub repository.',
                'error' => 'GitHub repository not configured.',
            ];
        }

        $archivePath = null;
        $extractPath = null;
        $backupPath = null;

        try {
            // 1. Download release archive
            $archivePath = $this->downloadReleaseArchive($repo, $versionTag);
            $logs[] = "Downloaded release archive for {$versionTag}.";

            // 2. Extract archive
            $extractPath = $this->extractReleaseArchive($archivePath, $versionTag);
            $logs[] = 'Extracted release archive.';

            // 3. Backup current code
            $backupPath = $this->backupCurrentCode($versionTag);
            $logs[] = "Backed up current code to {$backupPath}.";

            // 4. Deploy new code files
            $this->syncCodeFiles($extractPath);
            $logs[] = 'Synced new code files to application.';

            // 5. Run post-deployment tasks
            $postDeploy = $this->runPostDeploymentTasks();
            $logs = array_merge($logs, $postDeploy['logs']);

            if (!$postDeploy['success']) {
                throw new \RuntimeException('Post-deployment tasks failed: ' . ($postDeploy['error'] ?? 'Unknown'));
            }

            // 6. Update version.txt
            file_put_contents(base_path('version.txt'), $versionTag . PHP_EOL);
            $logs[] = "Updated version.txt to {$versionTag}.";

            return [
                'success' => true,
                'output' => implode("\n", $logs),
                'error' => null,
            ];

        } catch (\Throwable $e) {
            $logs[] = 'ERROR: ' . $e->getMessage();

            // Rollback code if we had a backup
            if ($backupPath !== null) {
                try {
                    $this->restoreCodeFromBackup($backupPath);
                    $logs[] = 'Restored code from backup.';
                } catch (\Throwable $restoreError) {
                    $logs[] = 'Code restore failed: ' . $restoreError->getMessage();
                }
            }

            return [
                'success' => false,
                'output' => implode("\n", $logs),
                'error' => $e->getMessage(),
            ];

        } finally {
            // Cleanup temp files
            if ($archivePath !== null && is_file($archivePath)) {
                File::delete($archivePath);
            }
            if ($extractPath !== null && is_dir($extractPath)) {
                File::deleteDirectory($extractPath);
            }
        }
    }

    /**
     * Check if code deployment is enabled.
     */
    public function isCodeDeploymentEnabled(): bool
    {
        return (bool) config('updates.auto_deploy_code', false)
            || (bool) config('updates.allow_tenant_code_deploy', false);
    }

    /**
     * Run preflight checks before deployment.
     *
     * @return array{passed: bool, errors: array<string>}
     */
    protected function preflightChecks(): array
    {
        $errors = [];
        $minDiskMb = (int) config('updates.deployment.min_disk_space_mb', 500);

        // Check disk space
        $freeBytes = disk_free_space(base_path());

        if ($freeBytes !== false && ($freeBytes / 1024 / 1024) < $minDiskMb) {
            $freeMb = round($freeBytes / 1024 / 1024, 1);
            $errors[] = "Insufficient disk space: {$freeMb}MB available, {$minDiskMb}MB required.";
        }

        // Check write permissions
        $checkDirs = ['app', 'config', 'database', 'resources', 'routes'];
        foreach ($checkDirs as $dir) {
            $path = base_path($dir);
            if (is_dir($path) && !is_writable($path)) {
                $errors[] = "Directory not writable: {$dir}";
            }
        }

        return [
            'passed' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Resolve the GitHub repository string (owner/repo).
     */
    protected function resolveGitHubRepository(): ?string
    {
        $configuredRepo = trim((string) config('releases.github_repo', ''));

        if ($configuredRepo !== '' && preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $configuredRepo) === 1) {
            return $configuredRepo;
        }

        $releaseUrl = (string) config('services.github.latest_release_url', '');

        if (preg_match('#/repos/([^/]+/[^/]+)/releases(?:/latest)?#i', $releaseUrl, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        return null;
    }

    /**
     * Download a release archive from GitHub.
     */
    protected function downloadReleaseArchive(string $repo, string $versionTag): string
    {
        // Validate version tag to prevent path traversal
        if (!preg_match('/^v?\d+\.\d+\.\d+/', $versionTag)) {
            throw new \RuntimeException("Invalid version tag format: {$versionTag}");
        }

        $headers = $this->githubApiHeaders();
        $response = Http::withHeaders($headers)
            ->timeout(120)
            ->get("https://api.github.com/repos/{$repo}/zipball/{$versionTag}");

        if ($response->failed()) {
            throw new \RuntimeException(
                "Failed to download release archive: HTTP {$response->status()}"
            );
        }

        $tempDir = storage_path('app/update-temp');
        File::ensureDirectoryExists($tempDir);

        $safeTag = preg_replace('/[^A-Za-z0-9._-]/', '-', $versionTag);
        $archivePath = "{$tempDir}/{$safeTag}.zip";
        file_put_contents($archivePath, $response->body());

        return $archivePath;
    }

    /**
     * Extract a release ZIP archive.
     */
    protected function extractReleaseArchive(string $archivePath, string $versionTag): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is not available.');
        }

        $extractRoot = storage_path('app/update-temp/extract-' . md5($versionTag . '|' . microtime(true)));
        File::ensureDirectoryExists($extractRoot);

        $zip = new \ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Failed to open release archive.');
        }

        $zip->extractTo($extractRoot);
        $zip->close();

        // GitHub archives have a top-level directory (e.g., user-repo-hash/)
        $directories = File::directories($extractRoot);

        if (count($directories) === 1) {
            return $directories[0];
        }

        return $extractRoot;
    }

    /**
     * Backup current application code.
     */
    protected function backupCurrentCode(string $versionTag): string
    {
        $safeTag = preg_replace('/[^A-Za-z0-9._-]/', '-', $versionTag);
        $backupPath = storage_path('app/update-backups/' . now()->format('Ymd_His') . '_' . $safeTag);
        File::ensureDirectoryExists($backupPath);

        foreach ($this->replaceDirectories() as $directory) {
            $source = base_path($directory);
            if (is_dir($source)) {
                File::copyDirectory($source, "{$backupPath}/{$directory}");
            }
        }

        foreach ($this->rootFiles() as $file) {
            $source = base_path($file);
            if (is_file($source)) {
                $destination = "{$backupPath}/{$file}";
                File::ensureDirectoryExists(dirname($destination));
                File::copy($source, $destination);
            }
        }

        return $backupPath;
    }

    /**
     * Sync new code files into the application.
     */
    protected function syncCodeFiles(string $extractPath): void
    {
        // Replace directories (delete old, copy new)
        foreach ($this->replaceDirectories() as $directory) {
            $source = "{$extractPath}/{$directory}";
            if (!is_dir($source)) {
                continue;
            }

            $destination = base_path($directory);
            if (is_dir($destination)) {
                File::deleteDirectory($destination);
            }
            File::copyDirectory($source, $destination);
        }

        // Overlay directories (merge, don't delete existing)
        foreach ($this->overlayDirectories() as $directory) {
            $source = "{$extractPath}/{$directory}";
            if (is_dir($source)) {
                File::copyDirectory($source, base_path($directory));
            }
        }

        // Root files
        foreach ($this->rootFiles() as $file) {
            $source = "{$extractPath}/{$file}";
            if (!is_file($source)) {
                continue;
            }

            $destination = base_path($file);
            File::ensureDirectoryExists(dirname($destination));
            File::copy($source, $destination);
        }
    }

    /**
     * Restore code from a backup directory.
     */
    protected function restoreCodeFromBackup(string $backupPath): void
    {
        foreach ($this->replaceDirectories() as $directory) {
            $destination = base_path($directory);
            if (is_dir($destination)) {
                File::deleteDirectory($destination);
            }

            $source = "{$backupPath}/{$directory}";
            if (is_dir($source)) {
                File::copyDirectory($source, $destination);
            }
        }

        foreach ($this->rootFiles() as $file) {
            $source = "{$backupPath}/{$file}";
            $destination = base_path($file);

            if (is_file($source)) {
                File::ensureDirectoryExists(dirname($destination));
                File::copy($source, $destination);
            }
        }
    }

    /**
     * Run post-deployment tasks (composer, cache clear, etc.)
     *
     * @return array{success: bool, logs: array<string>, error: string|null}
     */
    protected function runPostDeploymentTasks(): array
    {
        $logs = [];
        $deployConfig = config('updates.deployment', []);

        // Composer install
        if ($deployConfig['run_composer_install'] ?? true) {
            $composerBin = (string) config('services.composer.binary', 'composer');
            $composer = new Process(
                [$composerBin, 'install', '--no-dev', '--no-interaction', '--optimize-autoloader'],
                base_path(),
                $this->composerEnvironment(),
            );
            $composer->setTimeout(600);
            $composer->run();

            if ($composer->isSuccessful()) {
                $logs[] = 'Composer install completed.';
            } else {
                return [
                    'success' => false,
                    'logs' => array_merge($logs, ['Composer install failed: ' . $composer->getErrorOutput()]),
                    'error' => 'Composer install failed.',
                ];
            }
        }

        // Clear and rebuild caches
        $clear = new Process([PHP_BINARY, base_path('artisan'), 'optimize:clear'], base_path());
        $clear->setTimeout(180);
        $clear->run();

        if ($clear->isSuccessful()) {
            $logs[] = 'Cache cleared.';
        } else {
            $logs[] = 'Warning: Cache clear returned non-zero exit.';
        }

        return [
            'success' => true,
            'logs' => $logs,
            'error' => null,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function replaceDirectories(): array
    {
        return ['app', 'config', 'database', 'resources', 'routes'];
    }

    /**
     * @return array<int, string>
     */
    protected function overlayDirectories(): array
    {
        return ['bootstrap', 'public'];
    }

    /**
     * @return array<int, string>
     */
    protected function rootFiles(): array
    {
        return [
            'artisan',
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'postcss.config.js',
            'tailwind.config.js',
            'vite.config.js',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function githubApiHeaders(): array
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'PayMonitor-App',
        ];

        $token = trim((string) config('services.github.token', ''));
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * @return array<string, string>
     */
    protected function composerEnvironment(): array
    {
        $composerHome = trim((string) (getenv('COMPOSER_HOME') ?: ''));
        $appData = trim((string) (getenv('APPDATA') ?: ''));

        if ($composerHome === '' && $appData !== '') {
            $composerHome = rtrim($appData, "\\/") . DIRECTORY_SEPARATOR . 'Composer';
        }

        if ($composerHome === '') {
            $composerHome = storage_path('app/composer-home');
        }

        if ($appData === '') {
            $appData = dirname($composerHome);
        }

        File::ensureDirectoryExists($composerHome);
        File::ensureDirectoryExists($appData);

        return [
            'COMPOSER_HOME' => $composerHome,
            'APPDATA' => $appData,
        ];
    }
}
