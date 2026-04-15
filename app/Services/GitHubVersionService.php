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

        $gitBin = config('services.git.binary', 'git');
        $composerBin = config('services.composer.binary', 'composer');

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

        $fetch = new Process([$gitBin, 'fetch', '--tags', 'origin'], base_path());
        $fetch->setTimeout(300);
        $fetch->run();
        
        $checkout = new Process([$gitBin, 'checkout', '--detach', $newVersion], base_path());
        $checkout->setTimeout(180);
        
        $composer = new Process(
            [$composerBin, 'install', '--no-dev', '--no-interaction'],
            base_path(),
            $this->buildComposerEnvironment(),
        );
        $composer->setTimeout(600);
        
        $clear = new Process([PHP_BINARY, base_path('artisan'), 'optimize:clear'], base_path());
        $clear->setTimeout(180);
        
        $success = false;
        
        if ($fetch->isSuccessful()) {
            $checkout->run();
            if ($checkout->isSuccessful()) {
                $composer->run();
                if ($composer->isSuccessful()) {
                    $clear->run();
                    $success = $clear->isSuccessful();
                }
            }
        }

        if ($success) {
            file_put_contents(base_path('version.txt'), $newVersion.PHP_EOL);
        }
        
        $formatOutput = function ($process) {
            if (! $process->isStarted()) {
                return '[skipped]';
            }
            $out = trim($process->getOutput()."\n".$process->getErrorOutput());
            return $out === '' ? '[no output]' : $out;
        };

        $output = trim(implode("\n", [
            '[git fetch --tags origin]',
            $formatOutput($fetch),
            "[git checkout --detach $newVersion]",
            $formatOutput($checkout),
            '[composer install --no-dev]',
            $formatOutput($composer),
            '[php artisan optimize:clear]',
            $formatOutput($clear),
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

    protected function releaseCacheStore(): CacheRepository
    {
        return Cache::store('file');
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
