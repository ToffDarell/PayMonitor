<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class GitHubVersionService
{
    protected const GIT_FETCH_MAX_ATTEMPTS = 3;

    public function getLatestRelease(): array
    {
        $resolver = fn (): array => $this->resolveLatestRelease();

        try {
            return $this->releaseCacheStore()->remember('github_latest_release', now()->addMinutes(30), $resolver);
        } catch (\Throwable) {
            return $resolver();
        }
    }

    protected function resolveLatestRelease(): array
    {
        return (function (): array {
            $headers = [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'PayMonitor-App',
            ];

            $token = (string) config('services.github.token', '');
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer '.$token;
            }

            try {
                $response = Http::withHeaders($headers)
                    ->timeout(15)
                    ->get((string) config('services.github.latest_release_url', 'https://api.github.com/repos/ToffDarell/PayMonitor/releases/latest'));
            } catch (\Throwable) {
                return $this->fallbackReleaseData();
            }

            if ($response->failed()) {
                return $this->fallbackReleaseData();
            }

            $data = $response->json();

            return [
                'version' => (string) ($data['tag_name'] ?? 'Unknown'),
                'name' => (string) ($data['name'] ?? 'Untitled Release'),
                'changelog' => (string) ($data['body'] ?? ''),
                'published_at' => $data['published_at'] ?? null,
                'url' => (string) ($data['html_url'] ?? ''),
                'found' => true,
            ];
        })();
    }

    protected function fallbackReleaseData(): array
    {
        return [
            'version' => 'Unknown',
            'name' => 'Unable to check',
            'changelog' => '',
            'published_at' => null,
            'url' => '',
            'found' => false,
        ];
    }

    public function getCurrentVersion(): string
    {
        $gitVersion = $this->currentGitTag();
        if ($gitVersion !== null) {
            return $gitVersion;
        }

        $versionFile = base_path('version.txt');

        if (! is_file($versionFile)) {
            return 'v1.0.0';
        }

        $version = trim((string) file_get_contents($versionFile));

        return $version !== '' ? $version : 'v1.0.0';
    }

    public function isUpdateAvailable(): bool
    {
        $latestRelease = $this->getLatestRelease();

        if (! (bool) ($latestRelease['found'] ?? false)) {
            return false;
        }

        $current = $this->getCurrentVersion();
        $latest = (string) ($latestRelease['version'] ?? '0');

        return version_compare(
            ltrim($latest, 'vV'),
            ltrim($current, 'vV'),
            '>'
        );
    }

    public function getUpdateInfo(): array
    {
        $latest = $this->getLatestRelease();
        $current = $this->getCurrentVersion();

        return [
            'current_version' => $current,
            'latest_version' => (string) ($latest['version'] ?? 'Unknown'),
            'update_available' => $this->isUpdateAvailable(),
            'release_name' => (string) ($latest['name'] ?? 'Unable to check'),
            'changelog' => (string) ($latest['changelog'] ?? ''),
            'published_at' => $latest['published_at'] ?? null,
            'release_url' => (string) ($latest['url'] ?? ''),
            'found' => (bool) ($latest['found'] ?? false),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function parseChangelog(string $markdown): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (str_starts_with($trimmed, '- ') || str_starts_with($trimmed, '* ')) {
                $items[] = trim(substr($trimmed, 2));
            }
        }

        if ($items !== []) {
            return $items;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $items[] = $trimmed;
            }

            if (count($items) >= 12) {
                break;
            }
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpdateHistory(): array
    {
        $path = storage_path('app/update_log.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn (mixed $entry): bool => is_array($entry)));
    }

    public function applyUpdate(?string $appliedBy = null): array
    {
        $latestRelease = $this->getLatestRelease();

        if (! (bool) ($latestRelease['found'] ?? false)) {
            return [
                'success' => false,
                'output' => 'GitHub latest release could not be retrieved.',
                'version' => 'Unknown',
                'message' => 'Update failed',
            ];
        }

        $newVersion = (string) ($latestRelease['version'] ?? 'Unknown');

        if (! $this->isUpdateAvailable()) {
            return [
                'success' => true,
                'output' => 'System is already on the latest version.',
                'version' => $this->getCurrentVersion(),
                'message' => 'Already up to date',
            ];
        }

        $gitBin = (string) config('services.git.binary', 'git');
        $composerBin = (string) config('services.composer.binary', 'composer');

        // Check for tracked modified files (dirty repo)
        $statusCheck = new Process([$gitBin, 'status', '--porcelain'], base_path());
        $statusCheck->run();
        
        // Count lines that start with M, A, D, R, etc. (indicating tracked modifications)
        $isDirty = false;
        if ($statusCheck->isSuccessful()) {
            $lines = explode("\n", trim($statusCheck->getOutput()));
            foreach ($lines as $line) {
                if (preg_match('/^[MADRCU]. /', $line) || preg_match('/^.[MADRCU] /', $line)) {
                    $isDirty = true;
                    break;
                }
            }
        }

        if ($isDirty) {
            return [
                'success' => false,
                'output' => trim($statusCheck->getOutput()),
                'version' => $this->getCurrentVersion(),
                'message' => 'Update blocked by local changes',
            ];
        }

        $fetchResult = $this->runGitFetchWithRetry((string) $gitBin);
        $fetch = $fetchResult['process'];
        $archiveFallbackOutput = '[skipped]';
        $checkout = new Process([$gitBin, 'checkout', '--detach', $newVersion], base_path());
        $checkout->setTimeout(180);
        $composer = $this->buildComposerInstallProcess($composerBin);
        $clear = $this->buildOptimizeClearProcess();
        $success = false;
        
        if ($fetch->isSuccessful()) {
            $checkout->run();
            if ($checkout->isSuccessful()) {
                ['composer' => $composer, 'clear' => $clear, 'success' => $success] = $this->runPostUpdateProcesses($composerBin);
            }
        } else {
            $archiveFallback = $this->deployFromReleaseArchive($newVersion);
            $archiveFallbackOutput = $archiveFallback['output'];

            if ($archiveFallback['success']) {
                ['composer' => $composer, 'clear' => $clear, 'success' => $success] = $this->runPostUpdateProcesses($composerBin);
            }
        }

        if ($success) {
            file_put_contents(base_path('version.txt'), $newVersion.PHP_EOL);
        }
        
        $output = trim(implode("\n", [
            '[git fetch --tags origin]',
            $fetchResult['output'],
            "[git checkout --detach $newVersion]",
            $this->formatProcessOutput($checkout),
            '[release archive fallback]',
            $archiveFallbackOutput,
            '[composer install --no-dev]',
            $this->formatProcessOutput($composer),
            '[php artisan optimize:clear]',
            $this->formatProcessOutput($clear),
        ]));

        $this->appendUpdateHistory([
            'version' => $newVersion,
            'applied_at' => now()->format('Y-m-d H:i:s'),
            'applied_by' => $appliedBy ?: 'superadmin@paymonitor.com',
            'status' => $success ? 'success' : 'failed',
        ]);

        try {
            $releaseCache = $this->releaseCacheStore();
            $releaseCache->forget('github_latest_release');
            $releaseCache->forget('github_latest_release_info');
        } catch (\Throwable) {
            // Swallow cache reset errors so update flow doesn't fail for cache backend differences.
        }

        return [
            'success' => $success,
            'output' => $output,
            'version' => $newVersion,
            'message' => $success
                ? 'Update applied successfully'
                : 'Update failed',
        ];
    }

    /**
     * @return array{process: Process, output: string}
     */
    protected function runGitFetchWithRetry(string $gitBin): array
    {
        $attemptLogs = [];
        $lastProcess = null;

        for ($attempt = 1; $attempt <= self::GIT_FETCH_MAX_ATTEMPTS; $attempt++) {
            $process = new Process([$gitBin, 'fetch', '--tags', 'origin'], base_path());
            $process->setTimeout(300);
            $process->run();

            $lastProcess = $process;
            $attemptLogs[] = sprintf('[attempt %d/%d]', $attempt, self::GIT_FETCH_MAX_ATTEMPTS);
            $attemptLogs[] = $this->formatProcessOutput($process);

            if ($process->isSuccessful()) {
                break;
            }

            if ($attempt === self::GIT_FETCH_MAX_ATTEMPTS || ! $this->isTransientGitFetchFailure($process)) {
                break;
            }

            $attemptLogs[] = '[retrying after transient git network failure]';
            usleep($attempt * 500_000);
        }

        return [
            'process' => $lastProcess ?? new Process([$gitBin, 'fetch', '--tags', 'origin'], base_path()),
            'output' => trim(implode("\n", $attemptLogs)),
        ];
    }

    /**
     * @return array{success: bool, output: string}
     */
    protected function deployFromReleaseArchive(string $versionTag): array
    {
        $repo = $this->resolveGitHubRepository();

        if ($repo === null) {
            return [
                'success' => false,
                'output' => 'Unable to determine GitHub repository for archive fallback.',
            ];
        }

        $archivePath = null;
        $extractPath = null;
        $backupPath = null;
        $logs = [];

        try {
            $archivePath = $this->downloadReleaseArchive($repo, $versionTag);
            $logs[] = "Downloaded GitHub release archive for {$versionTag} from {$repo}.";

            $extractPath = $this->extractReleaseArchive($archivePath, $versionTag);
            $backupPath = $this->backupArchiveDeploymentTargets($versionTag);
            $logs[] = "Backed up current deployment files to {$backupPath}.";

            $this->copyReleaseArchiveToApplication($extractPath);
            $logs[] = 'Copied release archive into application directories.';

            return [
                'success' => true,
                'output' => trim(implode("\n", $logs)),
            ];
        } catch (\Throwable $exception) {
            $logs[] = $exception->getMessage();

            if ($backupPath !== null) {
                try {
                    $this->restoreArchiveDeploymentBackup($backupPath);
                    $logs[] = "Restored deployment files from backup {$backupPath}.";
                } catch (\Throwable $restoreException) {
                    $logs[] = 'Backup restore failed: '.$restoreException->getMessage();
                }
            }

            return [
                'success' => false,
                'output' => trim(implode("\n", $logs)),
            ];
        } finally {
            if ($archivePath !== null && is_file($archivePath)) {
                File::delete($archivePath);
            }

            if ($extractPath !== null && is_dir($extractPath)) {
                File::deleteDirectory($extractPath);
            }
        }
    }

    protected function releaseCacheStore(): CacheRepository
    {
        return Cache::store('file');
    }

    protected function currentGitTag(): ?string
    {
        $gitBin = (string) config('services.git.binary', 'git');
        $process = new Process([$gitBin, 'describe', '--tags', '--exact-match'], base_path());
        $process->setTimeout(10);

        try {
            $process->run();
        } catch (\Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        $tag = trim($process->getOutput());

        return $tag !== '' ? $tag : null;
    }

    /**
     * @return array<string, string>
     */
    protected function buildComposerEnvironment(): array
    {
        $composerHome = trim((string) (getenv('COMPOSER_HOME') ?: ''));
        $appData = trim((string) (getenv('APPDATA') ?: ''));

        if ($composerHome === '' && $appData !== '') {
            $composerHome = rtrim($appData, "\\/").DIRECTORY_SEPARATOR.'Composer';
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

    protected function buildComposerInstallProcess(string $composerBin): Process
    {
        $process = new Process(
            [$composerBin, 'install', '--no-dev', '--no-interaction'],
            base_path(),
            $this->buildComposerEnvironment(),
        );
        $process->setTimeout(600);

        return $process;
    }

    protected function buildOptimizeClearProcess(): Process
    {
        $process = new Process([PHP_BINARY, base_path('artisan'), 'optimize:clear'], base_path());
        $process->setTimeout(180);

        return $process;
    }

    /**
     * @return array{composer: Process, clear: Process, success: bool}
     */
    protected function runPostUpdateProcesses(string $composerBin): array
    {
        $composer = $this->buildComposerInstallProcess($composerBin);
        $clear = $this->buildOptimizeClearProcess();
        $success = false;

        $composer->run();

        if ($composer->isSuccessful()) {
            $clear->run();
            $success = $clear->isSuccessful();
        }

        return [
            'composer' => $composer,
            'clear' => $clear,
            'success' => $success,
        ];
    }

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

    protected function downloadReleaseArchive(string $repo, string $versionTag): string
    {
        $request = Http::withHeaders($this->githubApiHeaders())
            ->timeout(120);

        $response = $request->get("https://api.github.com/repos/{$repo}/zipball/{$versionTag}");

        if ($response->failed()) {
            throw new \RuntimeException('Failed to download GitHub release archive: '.$response->status().' '.$response->body());
        }

        $tempDirectory = storage_path('app/update-temp');
        File::ensureDirectoryExists($tempDirectory);

        $archivePath = $tempDirectory.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], '-', $versionTag).'.zip';
        file_put_contents($archivePath, $response->body());

        return $archivePath;
    }

    protected function extractReleaseArchive(string $archivePath, string $versionTag): string
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive is not available in this PHP environment.');
        }

        $extractRoot = storage_path('app/update-temp/extract-'.md5($versionTag.'|'.microtime(true)));
        File::ensureDirectoryExists($extractRoot);

        $zip = new \ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Failed to open downloaded GitHub release archive.');
        }

        $zip->extractTo($extractRoot);
        $zip->close();

        $directories = File::directories($extractRoot);

        if (count($directories) === 1) {
            return $directories[0];
        }

        return $extractRoot;
    }

    protected function backupArchiveDeploymentTargets(string $versionTag): string
    {
        $backupPath = storage_path('app/update-backups/'.now()->format('Ymd_His').'_'.preg_replace('/[^A-Za-z0-9._-]/', '-', $versionTag));
        File::ensureDirectoryExists($backupPath);

        foreach ($this->archiveReplaceDirectories() as $directory) {
            $source = base_path($directory);
            if (is_dir($source)) {
                File::copyDirectory($source, $backupPath.DIRECTORY_SEPARATOR.$directory);
            }
        }

        foreach ($this->archiveOverlayDirectories() as $directory) {
            $source = base_path($directory);
            if (is_dir($source)) {
                File::copyDirectory($source, $backupPath.DIRECTORY_SEPARATOR.$directory);
            }
        }

        foreach ($this->archiveRootFiles() as $file) {
            $source = base_path($file);
            if (! is_file($source)) {
                continue;
            }

            $destination = $backupPath.DIRECTORY_SEPARATOR.$file;
            File::ensureDirectoryExists(dirname($destination));
            File::copy($source, $destination);
        }

        return $backupPath;
    }

    protected function restoreArchiveDeploymentBackup(string $backupPath): void
    {
        foreach ($this->archiveReplaceDirectories() as $directory) {
            $destination = base_path($directory);

            if (is_dir($destination)) {
                File::deleteDirectory($destination);
            }

            $source = $backupPath.DIRECTORY_SEPARATOR.$directory;
            if (is_dir($source)) {
                File::copyDirectory($source, $destination);
            }
        }

        foreach ($this->archiveOverlayDirectories() as $directory) {
            $destination = base_path($directory);

            if (is_dir($destination)) {
                File::deleteDirectory($destination);
            }

            $source = $backupPath.DIRECTORY_SEPARATOR.$directory;
            if (is_dir($source)) {
                File::copyDirectory($source, $destination);
            }
        }

        foreach ($this->archiveRootFiles() as $file) {
            $destination = base_path($file);
            $source = $backupPath.DIRECTORY_SEPARATOR.$file;

            if (is_file($source)) {
                File::ensureDirectoryExists(dirname($destination));
                File::copy($source, $destination);
                continue;
            }

            if (is_file($destination)) {
                File::delete($destination);
            }
        }
    }

    protected function copyReleaseArchiveToApplication(string $extractPath): void
    {
        foreach ($this->archiveReplaceDirectories() as $directory) {
            $source = $extractPath.DIRECTORY_SEPARATOR.$directory;
            if (! is_dir($source)) {
                continue;
            }

            $destination = base_path($directory);
            if (is_dir($destination)) {
                File::deleteDirectory($destination);
            }

            File::copyDirectory($source, $destination);
        }

        foreach ($this->archiveOverlayDirectories() as $directory) {
            $source = $extractPath.DIRECTORY_SEPARATOR.$directory;
            if (is_dir($source)) {
                File::copyDirectory($source, base_path($directory));
            }
        }

        foreach ($this->archiveRootFiles() as $file) {
            $source = $extractPath.DIRECTORY_SEPARATOR.$file;
            $destination = base_path($file);

            if (! is_file($source)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($destination));
            File::copy($source, $destination);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function archiveReplaceDirectories(): array
    {
        return ['app', 'config', 'database', 'resources', 'routes'];
    }

    /**
     * @return array<int, string>
     */
    protected function archiveOverlayDirectories(): array
    {
        return ['bootstrap', 'public'];
    }

    /**
     * @return array<int, string>
     */
    protected function archiveRootFiles(): array
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
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $headers;
    }

    protected function isTransientGitFetchFailure(Process $process): bool
    {
        $output = strtolower(trim($process->getOutput()."\n".$process->getErrorOutput()));

        if ($output === '') {
            return false;
        }

        foreach ([
            'getaddrinfo() thread failed to start',
            'could not resolve host',
            'temporary failure in name resolution',
            'failed to connect to github.com',
            'failed to connect to github.com port 443',
            'connection timed out',
            'operation timed out',
            'connection reset by peer',
            'connection was reset',
            'remote end hung up unexpectedly',
            'early eof',
            'http/2 stream',
            'gnutls_handshake() failed',
            'recv failure: connection was reset',
        ] as $needle) {
            if (str_contains($output, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function formatProcessOutput(Process $process): string
    {
        if (! $process->isStarted()) {
            return '[skipped]';
        }

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return $output === '' ? '[no output]' : $output;
    }

    /**
     * @param  array<string, string>  $entry
     */
    protected function appendUpdateHistory(array $entry): void
    {
        $path = storage_path('app/update_log.json');
        $history = $this->getUpdateHistory();

        array_unshift($history, $entry);

        $history = array_values(array_slice($history, 0, 20));

        File::ensureDirectoryExists(dirname($path));
        file_put_contents(
            $path,
            json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }
}
