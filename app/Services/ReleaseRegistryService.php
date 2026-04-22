<?php

namespace App\Services;

use App\Models\AppRelease;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReleaseRegistryService
{
    public function __construct(
        private TenantUpdateService $tenantUpdateService,
        private string $githubRepo = '',
        private ?string $githubToken = null
    ) {
        $this->githubRepo = trim((string) (config('releases.github_repo') ?? ''));
        $this->githubToken = $this->normalizeToken(config('releases.github_token'));

        if ($this->githubRepo === '') {
            $this->githubRepo = $this->inferRepoFromReleaseUrl(
                (string) config('services.github.latest_release_url', '')
            );
        }
    }

    public function syncFromGitHub(): array
    {
        try {
            $releases = $this->fetchGitHubReleases();

            $synced = 0;
            $skipped = 0;

            foreach ($releases as $release) {
                if ($this->syncRelease($release)) {
                    $synced++;
                } else {
                    $skipped++;
                }
            }

            $notified = $this->syncTenantAvailabilityStates();

            Log::info('GitHub releases synced', [
                'synced' => $synced,
                'skipped' => $skipped,
                'notified' => $notified,
            ]);

            return [
                'success' => true,
                'synced' => $synced,
                'skipped' => $skipped,
                'notified' => $notified,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to sync GitHub releases', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchGitHubReleases(): array
    {
        $this->validateGitHubRepositoryConfig();

        $url = "https://api.github.com/repos/{$this->githubRepo}/releases";

        $request = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'PayMonitor-App',
        ]);

        if ($this->githubToken !== null) {
            $request = $request->withToken($this->githubToken);
        }

        $response = $request->get($url);

        if ($response->failed()) {
            throw new \RuntimeException($this->buildGitHubApiErrorMessage($response));
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    private function validateGitHubRepositoryConfig(): void
    {
        if ($this->githubRepo === '' || $this->githubRepo === 'your-org/paymonitor') {
            throw new \RuntimeException(
                'Missing GitHub repository configuration. Set GITHUB_REPO in .env using owner/repo format.'
            );
        }

        if (preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $this->githubRepo) !== 1) {
            throw new \RuntimeException(
                "Invalid GITHUB_REPO format '{$this->githubRepo}'. Expected owner/repo."
            );
        }
    }

    private function buildGitHubApiErrorMessage(Response $response): string
    {
        $status = $response->status();
        $apiMessage = $response->json('message');

        if (!is_string($apiMessage) || $apiMessage === '') {
            $apiMessage = trim((string) $response->body());
        }

        $message = "GitHub API request failed ({$status})";
        if ($apiMessage !== '') {
            $message .= ": {$apiMessage}";
        }

        if ($status === 404) {
            $message .= '. Verify GITHUB_REPO points to an existing repository and GITHUB_TOKEN can access it.';
        }

        if ($status === 401 || $status === 403) {
            $message .= '. Verify GITHUB_TOKEN has permission for the repository.';
        }

        return $message;
    }

    private function inferRepoFromReleaseUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (preg_match('#/repos/([^/]+/[^/]+)/releases(?:/latest)?#i', $url, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        return '';
    }

    private function normalizeToken(mixed $token): ?string
    {
        if (!is_string($token)) {
            return null;
        }

        $trimmed = trim($token);

        return $trimmed === '' ? null : $trimmed;
    }

    private function syncRelease(array $release): bool
    {
        $tag = trim((string) ($release['tag_name'] ?? ''));

        if ($tag === '' || (bool) ($release['draft'] ?? false)) {
            return false;
        }

        $publishedAt = $release['published_at'] ?? null;

        if (! is_string($publishedAt) || trim($publishedAt) === '') {
            return false;
        }

        /** @var AppRelease|null $existing */
        $existing = AppRelease::query()->where('tag', $tag)->first();

        $data = [
            'tag' => $tag,
            'title' => $release['name'] ?? $tag,
            'changelog' => $release['body'] ?? null,
            'release_url' => (string) ($release['html_url'] ?? ''),
            'published_at' => $publishedAt,
            'is_stable' => ! (bool) ($release['prerelease'] ?? false),
            'synced_at' => now(),
        ];

        if ($existing) {
            $existing->update($data);
            return false;
        }

        AppRelease::create($data);
        return true;
    }

    public function getLatestStableRelease(): ?AppRelease
    {
        return AppRelease::stable()->latest('published_at')->first();
    }

    public function getLatestRelease(): ?AppRelease
    {
        return AppRelease::latest('published_at')->first();
    }

    public function markAsRequired(int $releaseId, ?\DateTime $gracePeriod = null): void
    {
        AppRelease::where('id', $releaseId)->update(['is_required' => true]);
    }

    private function syncTenantAvailabilityStates(): int
    {
        $latestStable = $this->getLatestStableRelease();

        if (! $latestStable) {
            return 0;
        }

        $notified = 0;
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        foreach (DB::connection($centralConnection)->table('tenants')->pluck('id') as $tenantId) {
            if ($this->tenantUpdateService->syncAvailabilityForTenant((string) $tenantId, $latestStable)) {
                $notified++;
            }
        }

        return $notified;
    }
}
