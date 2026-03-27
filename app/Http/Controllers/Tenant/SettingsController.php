<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Models\TenantVersionAcknowledgement;
use App\Models\TenantSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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

        return view('settings.index', [
            'settings' => $settings,
            'activeTab' => in_array($activeTab, ['general', 'appearance', 'updates'], true) ? $activeTab : 'general',
            ...$updateData,
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

        $validated = $request->validate([
            'cooperative_tagline' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'date_format' => ['required', Rule::in(['M d, Y', 'd/m/Y', 'Y-m-d'])],
            'items_per_page' => ['required', 'integer', Rule::in([10, 15, 25, 50])],
            'accent_color' => ['required', Rule::in(['green', 'blue', 'indigo', 'purple', 'teal'])],
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
        ] as $key) {
            TenantSetting::set($key, (string) ($validated[$key] ?? ''));
        }

        TenantSetting::set('items_per_page', (string) $validated['items_per_page']);
        TenantSetting::set('show_member_photos', $request->boolean('show_member_photos') ? '1' : '0');

        $activeTab = (string) $request->input('active_tab', 'general');

        return redirect('/settings?tab='.urlencode($activeTab))->with('success', 'Settings updated successfully.');
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

    protected function versionTablesExist(): bool
    {
        $centralConnection = config('tenancy.database.central_connection');

        return Schema::connection($centralConnection)->hasTable('app_versions')
            && Schema::connection($centralConnection)->hasTable('tenant_version_acknowledgements');
    }
}
