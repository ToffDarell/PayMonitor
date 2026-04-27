<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantUpdate;
use App\Models\SupportRequest;
use App\Models\TenantSetting;
use App\Mail\TenantSupportRequestMail;
use App\Services\TenantUpdateService;
use App\Services\TenantSelfUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        TenantSetting::ensureDefaults();

        $settings = TenantSetting::allKeyed();
        $activeTab = (string) ($request->query('tab', $request->routeIs('settings.updates') ? 'updates' : 'general'));
        $passwordErrors = $this->passwordErrorBag($request);

        if ($passwordErrors !== null && $passwordErrors->isNotEmpty()) {
            $activeTab = 'security';
        }

        $updateData = $this->resolveUpdateData();
        $supportData = $this->resolveSupportData($request);

        return view('settings.index', [
            'settings' => $settings,
            'activeTab' => in_array($activeTab, ['general', 'appearance', 'security', 'updates', 'support'], true) ? $activeTab : 'general',
            ...$updateData,
            ...$supportData,
        ]);
    }

    public function updates(Request $request): View
    {
        $request->query->set('tab', 'updates');

        return $this->index($request);
    }

    public function update(Request $request): RedirectResponse
    {
        TenantSetting::ensureDefaults();
        $currentSettings = TenantSetting::allKeyed();

        $validated = $request->validate([
            'cooperative_tagline' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'date_format' => ['required', Rule::in(['M d, Y', 'd/m/Y', 'Y-m-d'])],
            'items_per_page' => ['required', 'integer', Rule::in([10, 15, 25, 50])],
            'accent_color' => ['required', Rule::in(['green', 'blue', 'indigo', 'purple', 'teal'])],
            'theme_mode' => ['required', Rule::in(['dark', 'light'])],
            'font_scale' => ['required', Rule::in(['compact', 'comfortable', 'large'])],
            'show_member_photos' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $logo = $request->file('logo');

        if ($logo instanceof UploadedFile) {
            if (! $logo->isValid()) {
                $message = $logo->getErrorMessage();

                if (in_array($logo->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    $message = 'The selected logo is too large. Please use an image smaller than 2 MB.';
                }

                throw ValidationException::withMessages([
                    'logo' => $message,
                ]);
            }

            $existingLogoPath = TenantSetting::get('logo_path');

            if (filled($existingLogoPath) && Storage::disk('public')->exists((string) $existingLogoPath)) {
                Storage::disk('public')->delete((string) $existingLogoPath);
            }

            try {
                $logoPath = 'tenant-assets/logos/'.$logo->hashName();
                $stored = Storage::disk('public')->put($logoPath, $logo->get());

                if (! $stored) {
                    throw ValidationException::withMessages([
                        'logo' => 'The selected logo could not be saved. Please try again.',
                    ]);
                }
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'logo' => 'The selected logo could not be saved. Please try a smaller image or choose the file again.',
                ]);
            }

            TenantSetting::set('logo_path', $logoPath);
        }

        foreach ([
            'cooperative_tagline',
            'contact_number',
            'contact_email',
            'address',
            'currency_symbol',
            'date_format',
            'accent_color',
            'theme_mode',
            'font_scale',
        ] as $key) {
            TenantSetting::set($key, (string) ($validated[$key] ?? ($currentSettings[$key] ?? '')));
        }

        TenantSetting::set('items_per_page', (string) ($validated['items_per_page'] ?? ($currentSettings['items_per_page'] ?? '15')));
        TenantSetting::set(
            'show_member_photos',
            array_key_exists('show_member_photos', $validated)
                ? ($request->boolean('show_member_photos') ? '1' : '0')
                : ($currentSettings['show_member_photos'] ?? '0')
        );

        $activeTab = (string) $request->input('active_tab', 'general');

        return redirect('/settings?tab='.urlencode($activeTab))->with('success', 'Settings updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()?->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect('/settings?tab=security')->with('success', 'Password updated successfully.');
    }

    public function submitSupport(Request $request): RedirectResponse
    {
        abort_unless($this->supportRequestsTableExists(), 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['general', 'technical', 'billing', 'account', 'feature'])],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $tenantModel = tenant();
        $user = $request->user();

        $supportRequest = SupportRequest::query()->create([
            'tenant_id' => (string) ($tenantModel?->id ?? request()->route('tenant')),
            'tenant_name' => (string) ($tenantModel?->name ?? 'Tenant Workspace'),
            'requester_name' => (string) ($user?->name ?? ($tenantModel?->admin_name ?? 'Tenant User')),
            'requester_email' => (string) ($user?->email ?? ($tenantModel?->email ?? config('mail.from.address', 'support@paymonitor.test'))),
            'category' => $validated['category'],
            'subject' => trim((string) $validated['subject']),
            'message' => trim((string) $validated['message']),
            'status' => 'open',
        ]);

        $supportEmail = (string) config('app.support_email', config('mail.from.address', 'support@paymonitor.test'));

        try {
            Mail::to($supportEmail)->send(new TenantSupportRequestMail($supportRequest, $tenantModel, $user));
        } catch (\Throwable) {
            // Keep the support request saved even if the mail transport is unavailable.
        }

        return redirect('/settings?tab=support')->with('success', 'Support request submitted successfully.');
    }

    public function applyUpdate(Request $request): RedirectResponse|JsonResponse
    {
        // app_releases lives in the central DB, not the tenant DB.
        // Use the central connection explicitly so the exists rule works.
        $centralConnection = (string) config('tenancy.database.central_connection', config('database.default'));
        $expectsJson = $request->expectsJson() || $request->ajax();

        $request->validate([
            'release_id' => [
                'required',
                Rule::exists("$centralConnection.app_releases", 'id'),
            ],
        ]);

        $tenantId = (string) (tenant()?->id ?? $request->route('tenant'));
        $releaseId = (int) $request->input('release_id');

        $selfUpdateService = app(TenantSelfUpdateService::class);
        $result = $selfUpdateService->applyUpdate($tenantId, $releaseId);

        if ($result['success']) {
            $details = $result['details'] ?? [];
            $version = $result['release']?->tag ?? 'v1.0.0';
            $migrations = $details['migrations_run'] ?? 0;
            $codeDeployed = ($details['code_deployed'] ?? false) ? ' with code deployment' : '';

            $message = "Successfully updated to {$version}{$codeDeployed}.";
            if ($migrations > 0) {
                $message .= " {$migrations} migration(s) applied.";
            }

            if ($expectsJson) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'version' => $version,
                    'details' => $details,
                ]);
            }

            return redirect('/settings?tab=updates')->with('success', $message);
        }

        $errorMessage = 'Update failed: ' . ($result['error'] ?? 'Unknown error');

        if ($expectsJson) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => (string) ($result['error'] ?? 'Unknown error'),
            ], 422);
        }

        return back()->with('error', $errorMessage);
    }

    public function createBackup(Request $request): RedirectResponse
    {
        $tenantId = (string) (tenant()?->id ?? $request->route('tenant'));
        $tenant = \App\Models\Tenant::findOrFail($tenantId);

        $backupService = app(\App\Services\TenantBackupService::class);
        $result = $backupService->createBackup($tenant, 'manual');

        if ($result['success']) {
            return redirect('/settings?tab=updates')->with('success', 'Backup created successfully.');
        }

        return back()->with('error', 'Backup failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    public function syncReleases(): RedirectResponse
    {
        $result = app(\App\Services\ReleaseRegistryService::class)->syncFromGitHub();

        if ($result['success']) {
            return redirect('/settings?tab=updates')->with('success', 'Successfully checked and synced latest releases from GitHub.');
        }

        return redirect('/settings?tab=updates')->with('error', 'Failed to sync updates: ' . $result['error']);
    }

    /**
     * @return array{
     *     updateInfo: array<string, mixed>,
     *     changelogItems: array<int, string>,
     *     availableUpdates: array<int, array<string, mixed>>,
     *     updateHistory: Collection<int, TenantUpdate>,
     *     updateHistoryCount: int
     * }
     */
    protected function resolveUpdateData(): array
    {
        $tenantId = (string) (tenant()?->id ?? request()->route('tenant'));
        $tenantUpdateService = app(TenantUpdateService::class);
        $currentRelease = $tenantUpdateService->getCurrentRelease($tenantId);
        $updateHistoryQuery = TenantUpdate::query()
            ->forTenant($tenantId)
            ->with('appRelease')
            ->where('status', '!=', TenantUpdate::STATUS_UPDATE_AVAILABLE);
        $availableUpdates = array_map(function (array $update): array {
            $update['changelog_items'] = $this->parseChangelog((string) ($update['changelog'] ?? ''));

            return $update;
        }, $tenantUpdateService->getAvailableUpdates($tenantId));
        $latestAvailable = $availableUpdates[0] ?? [];
        $changelogText = (string) ($latestAvailable['changelog'] ?? '');

        $updateInfo = [
            'current_version' => (string) ($currentRelease?->appRelease?->tag ?? 'v1.0.0'),
            'latest_version' => (string) ($latestAvailable['tag'] ?? $currentRelease?->appRelease?->tag ?? 'v1.0.0'),
            'release_name' => (string) ($latestAvailable['title'] ?? ($currentRelease?->appRelease?->title ?? 'No published release')),
            'release_url' => (string) ($latestAvailable['release_url'] ?? ''),
            'published_at' => $latestAvailable['published_at'] ?? null,
            'changelog' => $changelogText,
            'update_available' => ! empty($availableUpdates),
        ];

        return [
            'updateInfo' => $updateInfo,
            'changelogItems' => $latestAvailable['changelog_items'] ?? $this->parseChangelog($changelogText),
            'availableUpdates' => $availableUpdates,
            'updateHistory' => (clone $updateHistoryQuery)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
            'updateHistoryCount' => (clone $updateHistoryQuery)->count(),
        ];
    }

    /**
     * @return array{
     *     supportRequests: Collection<int, SupportRequest>,
     *     supportContact: array{email: string, phone: string, hours: string}
     * }
     */
    protected function resolveSupportData(Request $request): array
    {
        $supportContact = [
            'email' => (string) config('app.support_email', config('mail.from.address', 'support@paymonitor.test')),
            'phone' => (string) config('app.support_phone', '+63 917 000 0000'),
            'hours' => (string) config('app.support_hours', 'Mon-Fri, 8:00 AM - 5:00 PM'),
        ];

        if (! $this->supportRequestsTableExists()) {
            return [
                'supportRequests' => collect(),
                'supportContact' => $supportContact,
            ];
        }

        $tenantModel = tenant();
        $tenantId = (string) ($tenantModel?->id ?? $request->route('tenant'));

        return [
            'supportRequests' => SupportRequest::query()
                ->with('responses')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(10)
                ->get(),
            'supportContact' => $supportContact,
        ];
    }

    protected function supportRequestsTableExists(): bool
    {
        $centralConnection = config('tenancy.database.central_connection');

        return Schema::connection($centralConnection)->hasTable('support_requests');
    }

    private function passwordErrorBag(Request $request): ?MessageBag
    {
        $errors = $request->session()->get('errors');

        if (! $errors instanceof \Illuminate\Support\ViewErrorBag || ! $errors->hasBag('updatePassword')) {
            return null;
        }

        return $errors->getBag('updatePassword');
    }

    /**
     * @return array<int, string>
     */
    private function parseChangelog(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];

        $items = collect($lines)
            ->map(static fn (string $line): string => trim($line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->map(static function (string $line): string {
                return trim((string) preg_replace('/^[-*]\s+/', '', $line));
            })
            ->values()
            ->all();

        return $items;
    }
}
