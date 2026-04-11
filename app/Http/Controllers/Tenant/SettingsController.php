<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Models\SupportRequest;
use App\Models\TenantVersionAcknowledgement;
use App\Models\TenantSetting;
use App\Mail\TenantSupportRequestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        TenantSetting::ensureDefaults();

        $settings = TenantSetting::allKeyed();
        $activeTab = (string) ($request->query('tab', $request->routeIs('settings.updates') ? 'updates' : 'general'));
        $updateData = $this->resolveUpdateData();
        $supportData = $this->resolveSupportData($request);

        return view('settings.index', [
            'settings' => $settings,
            'activeTab' => in_array($activeTab, ['general', 'appearance', 'updates', 'support'], true) ? $activeTab : 'general',
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

        $supportEmail = (string) config('mail.from.address', 'support@paymonitor.test');

        try {
            Mail::to($supportEmail)->send(new TenantSupportRequestMail($supportRequest, $tenantModel, $user));
        } catch (\Throwable) {
            // Keep the support request saved even if the mail transport is unavailable.
        }

        return redirect('/settings?tab=support')->with('success', 'Support request submitted successfully.');
    }

    public function acknowledge(Request $request, string $tenant, AppVersion $version): RedirectResponse
    {
        $tenantModel = tenant();
        abort_if($tenantModel === null, 404);
        abort_unless($this->versionTablesExist(), 404);

        TenantVersionAcknowledgement::query()->updateOrCreate(
            [
                'tenant_id' => $tenantModel->id,
                'version_id' => $version->id,
            ],
            [
                'acknowledged_at' => now(),
            ],
        );

        return back()->with('success', "Version {$version->version_number} acknowledged.");
    }

    /**
     * @return array{
     *     latestVersion: ?AppVersion,
     *     currentVersion: ?AppVersion,
     *     versions: Collection<int, AppVersion>,
     *     acknowledgements: Collection<int|string, TenantVersionAcknowledgement>,
     *     latestVersionAcknowledged: bool
     * }
     */
    protected function resolveUpdateData(): array
    {
        if (! $this->versionTablesExist()) {
            return [
                'latestVersion' => null,
                'currentVersion' => null,
                'versions' => collect(),
                'acknowledgements' => collect(),
                'latestVersionAcknowledged' => false,
            ];
        }

        $tenantModel = tenant();
        $tenantId = $tenantModel?->id;

        $versions = AppVersion::query()
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get();

        $acknowledgements = $tenantId === null
            ? collect()
            : TenantVersionAcknowledgement::query()
                ->where('tenant_id', $tenantId)
                ->orderByDesc('acknowledged_at')
                ->get()
                ->keyBy('version_id');

        $latestVersion = AppVersion::latestActive();
        $currentVersion = $versions->first(fn (AppVersion $version): bool => $acknowledgements->has($version->id));
        $latestVersionAcknowledged = $latestVersion !== null && $acknowledgements->has($latestVersion->id);

        return [
            'latestVersion' => $latestVersion,
            'currentVersion' => $currentVersion,
            'versions' => $versions,
            'acknowledgements' => $acknowledgements,
            'latestVersionAcknowledged' => $latestVersionAcknowledged,
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
            'email' => (string) config('mail.from.address', 'support@paymonitor.test'),
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
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(10)
                ->get(),
            'supportContact' => $supportContact,
        ];
    }

    protected function versionTablesExist(): bool
    {
        $centralConnection = config('tenancy.database.central_connection');

        return Schema::connection($centralConnection)->hasTable('app_versions')
            && Schema::connection($centralConnection)->hasTable('tenant_version_acknowledgements');
    }

    protected function supportRequestsTableExists(): bool
    {
        $centralConnection = config('tenancy.database.central_connection');

        return Schema::connection($centralConnection)->hasTable('support_requests');
    }
}
