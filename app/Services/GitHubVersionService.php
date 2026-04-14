<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class GitHubVersionService
{
    public function getLatestRelease(): array
    {
        return Cache::remember('github_latest_release', now()->addMinutes(30), function (): array {
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
        });
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

        $git = Process::fromShellCommandline('git pull origin main', base_path());
        $git->setTimeout(300);
        $git->run();

        $composer = Process::fromShellCommandline('composer install --no-dev', base_path());
        $composer->setTimeout(600);
        $composer->run();

        $clear = new Process([PHP_BINARY, 'artisan', 'optimize:clear'], base_path());
        $clear->setTimeout(180);
        $clear->run();

        $success = $git->isSuccessful() && $composer->isSuccessful() && $clear->isSuccessful();

        if ($success) {
            file_put_contents(base_path('version.txt'), $newVersion.PHP_EOL);
        }

        $output = trim(implode("\n", [
            '[git pull]',
            trim($git->getOutput()."\n".$git->getErrorOutput()),
            '[composer install --no-dev]',
            trim($composer->getOutput()."\n".$composer->getErrorOutput()),
            '[php artisan optimize:clear]',
            trim($clear->getOutput()."\n".$clear->getErrorOutput()),
        ]));

        $this->appendUpdateHistory([
            'version' => $newVersion,
            'applied_at' => now()->format('Y-m-d H:i:s'),
            'applied_by' => $appliedBy ?: 'superadmin@paymonitor.com',
            'status' => $success ? 'success' : 'failed',
        ]);

        Cache::forget('github_latest_release');
        Cache::forget('github_latest_release_info');

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
