@extends('layouts.tenant')

@section('title', 'Settings')

@php
    $tenantParameter = ['tenant' => tenant()?->id ?? request()->route('tenant')];
    $logoPath = $settings['logo_path'] ?? null;
    $logoUrl = filled($logoPath)
        ? route('stancl.tenancy.asset', ['path' => ltrim((string) $logoPath, '/')], false)
        : null;
    $updateAvailable = (bool) ($updateInfo['update_available'] ?? false);
    $availableUpdateCount = count($availableUpdates ?? []);
    $historyCount = (int) ($updateHistoryCount ?? (isset($updateHistory) ? $updateHistory->count() : 0));
    $currentVersionLabel = (string) ($updateInfo['current_version'] ?? 'v1.0.0');
    $latestVersionLabel = (string) ($updateInfo['latest_version'] ?? 'Unknown');
    $releaseName = (string) ($updateInfo['release_name'] ?? 'Unable to check');
    $releaseUrl = (string) ($updateInfo['release_url'] ?? '');
    $releasePublishedLabel = filled($updateInfo['published_at'] ?? null)
        ? \Illuminate\Support\Carbon::parse((string) $updateInfo['published_at'])->format('M d, Y h:i A')
        : 'Unknown';
    $supportEmail = $supportContact['email'] ?? config('mail.from.address', 'support@paymonitor.test');
    $supportPhone = $supportContact['phone'] ?? '+63 917 000 0000';
    $supportHours = $supportContact['hours'] ?? 'Mon-Fri, 8:00 AM - 5:00 PM';
    $passwordHint = auth()->user()?->email ?? tenant()?->email ?? 'your account email';
    $canViewSettings = auth()->user()?->hasTenantPermission(\App\Support\TenantPermissions::SETTINGS_VIEW) ?? false;
    $canManageSettings = auth()->user()?->hasTenantPermission(\App\Support\TenantPermissions::SETTINGS_UPDATE) ?? false;
    $updatesOnly = request()->routeIs('settings.updates') && ! $canViewSettings;
@endphp

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .settings-tab-active {
        border-color: rgba(var(--pm-accent-rgb), 0.35);
        background-color: rgba(var(--pm-accent-rgb), 0.12);
        color: var(--pm-nav-hover-text);
    }

    .settings-tab-default {
        color: var(--pm-text-muted);
    }

    .settings-tab-default:hover {
        background-color: var(--pm-nav-hover-bg);
        color: var(--pm-nav-hover-text);
    }

    .settings-shell [class*="bg-white/[0.02]"] {
        background-color: var(--pm-panel-bg) !important;
    }

    .settings-shell [class*="bg-white/[0.03]"],
    .settings-shell [class*="bg-white/[0.06]"],
    .settings-shell [class*="bg-[#0f1319]"],
    .settings-shell [class*="bg-[#0b1120]"] {
        background-color: var(--pm-surface-bg) !important;
    }

    .settings-shell [class*="border-white/[0.07]"],
    .settings-shell [class*="border-white/[0.06]"],
    .settings-shell [class*="border-white/10"] {
        border-color: var(--pm-border) !important;
    }

    .settings-shell .text-white {
        color: var(--pm-text-primary) !important;
    }

    .settings-shell .text-slate-200,
    .settings-shell .text-slate-300 {
        color: var(--pm-text-secondary) !important;
    }

    .settings-shell .text-slate-400,
    .settings-shell .text-slate-500 {
        color: var(--pm-text-muted) !important;
    }

    .settings-shell input:not([type='hidden']):not([type='checkbox']):not([type='radio']),
    .settings-shell textarea,
    .settings-shell select {
        background-color: var(--pm-surface-bg) !important;
        border-color: var(--pm-border) !important;
        color: var(--pm-text-primary) !important;
    }

    .settings-shell input:not([type='hidden']):not([type='checkbox']):not([type='radio'])::placeholder,
    .settings-shell textarea::placeholder {
        color: var(--pm-text-subtle) !important;
    }

    .settings-shell input:not([type='hidden']):not([type='checkbox']):not([type='radio']):focus,
    .settings-shell textarea:focus,
    .settings-shell select:focus {
        border-color: var(--pm-accent) !important;
        box-shadow: 0 0 0 0.2rem rgba(var(--pm-accent-rgb), 0.18) !important;
    }

    .settings-support-status-open {
        border-color: rgba(59, 130, 246, 0.3);
        background-color: rgba(59, 130, 246, 0.1);
        color: #bfdbfe;
    }

    @keyframes settingsScaleIn {
        from {
            transform: scale(0.85);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes settingsFadeIn {
        from {
            opacity: 0;
            transform: translateY(6px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .settings-scale-in {
        animation: settingsScaleIn 320ms ease-out both;
    }

    .settings-log-line {
        animation: settingsFadeIn 240ms ease-out both;
    }

    .settings-update-panel {
        background-color: var(--pm-card-bg) !important;
        border-color: var(--pm-border) !important;
    }

    .settings-update-surface {
        background-color: var(--pm-surface-bg) !important;
        border-color: var(--pm-border) !important;
    }

    .settings-update-soft {
        background-color: var(--pm-panel-bg) !important;
        border-color: var(--pm-panel-border) !important;
    }

    .settings-update-heading {
        color: var(--pm-text-primary) !important;
    }

    .settings-update-body {
        color: var(--pm-text-secondary) !important;
    }

    .settings-update-muted {
        color: var(--pm-text-muted) !important;
    }

    .settings-update-subtle {
        color: var(--pm-text-subtle) !important;
    }

    .settings-step-pending {
        background-color: var(--pm-surface-bg) !important;
        color: var(--pm-text-muted) !important;
        border: 2px solid var(--pm-border) !important;
    }

    .settings-step-line {
        background-color: var(--pm-border) !important;
    }
</style>
@endpush

@section('content')
<div
    x-data="{
        activeTab: @js($activeTab),
        availableUpdatesOpen: false,
        historyOpen: false,
        logoPreview: @js($logoUrl),
        selectedAccent: @js($settings['accent_color'] ?? 'green'),
        selectedThemeMode: @js(old('theme_mode', $settings['theme_mode'] ?? 'dark')),
        handleLogoChange(event) {
            const [file] = event.target.files;
            if (!file) {
                this.logoPreview = @js($logoUrl);
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                this.logoPreview = loadEvent.target?.result ?? null;
            };
            reader.readAsDataURL(file);
        }
    }"
    class="settings-shell space-y-6"
>
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="font-heading text-2xl font-bold tracking-tight text-white">{{ $updatesOnly ? 'Tenant Updates' : 'Tenant Settings' }}</h2>
            <p class="mt-1 text-sm text-slate-400">
                {{ $updatesOnly ? 'Review the latest tenant release and apply it when your role allows updates.' : 'Customize your portal details, appearance, account security, and release update visibility.' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 rounded-2xl border border-white/[0.07] bg-white/[0.02] p-2">
            @if(! $updatesOnly)
                <button type="button" x-on:click="activeTab = 'general'" x-bind:class="activeTab === 'general' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">General</button>
                <button type="button" x-on:click="activeTab = 'appearance'" x-bind:class="activeTab === 'appearance' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Appearance</button>
                <button type="button" x-on:click="activeTab = 'security'" x-bind:class="activeTab === 'security' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Security</button>
            @endif
            <button type="button" x-on:click="activeTab = 'updates'" x-bind:class="activeTab === 'updates' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Updates</button>
            @if(! $updatesOnly)
                <button type="button" x-on:click="activeTab = 'support'" x-bind:class="activeTab === 'support' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Support</button>
            @endif
        </div>
    </div>

    @if(! $updatesOnly)
    <div x-cloak x-show="activeTab === 'general'" class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
        <form method="POST" action="{{ route('settings.update', $tenantParameter, false) }}" class="space-y-6">
            @csrf
            <input type="hidden" name="active_tab" value="general">
            <input type="hidden" name="accent_color" value="{{ old('accent_color', $settings['accent_color'] ?? 'green') }}">
            <input type="hidden" name="theme_mode" value="{{ old('theme_mode', $settings['theme_mode'] ?? 'dark') }}">
            <input type="hidden" name="font_scale" value="{{ old('font_scale', $settings['font_scale'] ?? 'comfortable') }}">
            <input type="hidden" name="show_member_photos" value="{{ old('show_member_photos', $settings['show_member_photos'] ?? '0') }}">

            <div class="grid gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="cooperative_tagline" class="mb-2 block text-sm font-medium text-slate-200">Cooperative Tagline</label>
                    <input id="cooperative_tagline" name="cooperative_tagline" type="text" value="{{ old('cooperative_tagline', $settings['cooperative_tagline'] ?? '') }}" placeholder="Your trusted lending cooperative" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                    @error('cooperative_tagline') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_number" class="mb-2 block text-sm font-medium text-slate-200">Contact Number</label>
                    <input id="contact_number" name="contact_number" type="text" value="{{ old('contact_number', $settings['contact_number'] ?? '') }}" placeholder="+63 912 345 6789" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                    @error('contact_number') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_email" class="mb-2 block text-sm font-medium text-slate-200">Contact Email</label>
                    <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" placeholder="support{{ '@' . request()->getHost() }}" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                    @error('contact_email') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="mb-2 block text-sm font-medium text-slate-200">Address</label>
                    <textarea id="address" name="address" rows="4" placeholder="Enter the cooperative address" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">{{ old('address', $settings['address'] ?? '') }}</textarea>
                    @error('address') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="currency_symbol" class="mb-2 block text-sm font-medium text-slate-200">Currency Symbol</label>
                    <input id="currency_symbol" name="currency_symbol" type="text" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '₱') }}" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                    @error('currency_symbol') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="date_format" class="mb-2 block text-sm font-medium text-slate-200">Date Format</label>
                    <select id="date_format" name="date_format" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                        @foreach(['M d, Y', 'd/m/Y', 'Y-m-d'] as $format)
                            <option value="{{ $format }}" @selected(old('date_format', $settings['date_format'] ?? 'M d, Y') === $format)>{{ $format }}</option>
                        @endforeach
                    </select>
                    @error('date_format') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="items_per_page" class="mb-2 block text-sm font-medium text-slate-200">Items Per Page</label>
                    <select id="items_per_page" name="items_per_page" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                        @foreach([10, 15, 25, 50] as $count)
                            <option value="{{ $count }}" @selected((int) old('items_per_page', $settings['items_per_page'] ?? 15) === $count)>{{ $count }}</option>
                        @endforeach
                    </select>
                    @error('items_per_page') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">Save Settings</button>
            </div>
        </form>
    </div>
    @endif

    @if(! $updatesOnly)
    <div x-cloak x-show="activeTab === 'appearance'" class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
        <form method="POST" action="{{ route('settings.update', $tenantParameter, false) }}" enctype="multipart/form-data" class="space-y-8">
            @csrf
            <input type="hidden" name="active_tab" value="appearance">
            <input type="hidden" name="cooperative_tagline" value="{{ old('cooperative_tagline', $settings['cooperative_tagline'] ?? '') }}">
            <input type="hidden" name="contact_number" value="{{ old('contact_number', $settings['contact_number'] ?? '') }}">
            <input type="hidden" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}">
            <input type="hidden" name="address" value="{{ old('address', $settings['address'] ?? '') }}">
            <input type="hidden" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '₱') }}">
            <input type="hidden" name="date_format" value="{{ old('date_format', $settings['date_format'] ?? 'M d, Y') }}">
            <input type="hidden" name="items_per_page" value="{{ old('items_per_page', $settings['items_per_page'] ?? 15) }}">
            <input type="hidden" name="theme_mode" value="{{ old('theme_mode', $settings['theme_mode'] ?? 'dark') }}">
            <input type="hidden" name="font_scale" value="{{ old('font_scale', $settings['font_scale'] ?? 'comfortable') }}">

            <div class="max-w-4xl space-y-8">
                <div class="space-y-6">
                    <div>
                        <p class="text-sm font-semibold text-white">Logo Upload</p>
                        <p class="mt-1 text-sm text-slate-500">Upload a square image up to 2MB. The logo appears in the tenant sidebar.</p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-dashed border-white/10 bg-[#0f1319] p-5 transition hover:border-white/20">
                        <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                            <template x-if="logoPreview">
                                <img x-bind:src="logoPreview" alt="Logo preview" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!logoPreview">
                                <svg class="h-8 w-8 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 19.5h16.5M4.5 16.5l4.318-4.318a1.125 1.125 0 0 1 1.591 0L13.5 15.273l1.818-1.818a1.125 1.125 0 0 1 1.591 0L19.5 16.5M6.75 8.25h.008v.008H6.75V8.25Z" />
                                </svg>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-white">Choose logo image</p>
                            <p class="mt-1 text-sm text-slate-500">PNG, JPG, or WEBP up to 2MB.</p>
                        </div>
                        <input type="file" name="logo" accept="image/*" class="hidden" x-on:change="handleLogoChange($event)">
                    </label>
                    @error('logo') <p class="text-xs text-red-400">{{ $message }}</p> @enderror

                    <div>
                        <p class="text-sm font-semibold text-white">Accent Color</p>
                        <p class="mt-1 text-sm text-slate-500">Choose the accent used in the sidebar, buttons, and highlights.</p>
                        <input type="hidden" name="accent_color" x-model="selectedAccent">
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                            @foreach(['green' => '#22c55e', 'blue' => '#3b82f6', 'indigo' => '#6366f1', 'purple' => '#a855f7', 'teal' => '#14b8a6'] as $name => $hex)
                                <button type="button" x-on:click="selectedAccent = '{{ $name }}'" x-bind:class="selectedAccent === '{{ $name }}' ? 'border-white/25 ring-2 ring-white/20' : 'border-white/10'" class="rounded-2xl border bg-[#0f1319] p-4 text-left transition hover:border-white/20">
                                    <span class="flex items-center justify-between">
                                        <span class="h-8 w-8 rounded-full" style="background-color: {{ $hex }}"></span>
                                        <svg x-show="selectedAccent === '{{ $name }}'" x-cloak class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                                        </svg>
                                    </span>
                                    <span class="mt-3 block text-sm font-medium capitalize text-white">{{ $name }}</span>
                                </button>
                            @endforeach
                        </div>
                        @error('accent_color') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="rounded-2xl border border-white/[0.07] bg-[#0f1319] p-5">
                        <p class="text-sm font-semibold text-white">Display Preferences</p>
                        <p class="mt-1 text-sm text-slate-500">Choose how your tenant portal feels for your staff.</p>

                        <div class="mt-5 space-y-5">
                            <div>
                                <p class="text-sm font-medium text-white">Theme Mode</p>
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    @foreach(['dark' => 'Dark Mode', 'light' => 'Light Mode'] as $mode => $label)
                                        <label class="cursor-pointer rounded-2xl border border-white/10 bg-white/[0.03] p-4 transition" x-bind:class="selectedThemeMode === '{{ $mode }}' ? 'border-white/25 ring-2 ring-white/20 bg-white/[0.06]' : 'hover:border-white/20'">
                                            <input type="radio" name="theme_mode" value="{{ $mode }}" class="hidden" x-model="selectedThemeMode" @checked(old('theme_mode', $settings['theme_mode'] ?? 'dark') === $mode)>
                                            <span class="block text-sm font-semibold text-white">{{ $label }}</span>
                                            <span class="mt-1 block text-xs text-slate-500">{{ $mode === 'dark' ? 'Keeps the current dark workspace look.' : 'Uses a brighter portal shell for daytime use.' }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('theme_mode') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="font_scale" class="mb-2 block text-sm font-medium text-white">Font Size</label>
                                <select id="font_scale" name="font_scale" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                                    <option value="compact" @selected(old('font_scale', $settings['font_scale'] ?? 'comfortable') === 'compact')>Compact</option>
                                    <option value="comfortable" @selected(old('font_scale', $settings['font_scale'] ?? 'comfortable') === 'comfortable')>Comfortable</option>
                                    <option value="large" @selected(old('font_scale', $settings['font_scale'] ?? 'comfortable') === 'large')>Large</option>
                                </select>
                                <p class="mt-2 text-sm text-slate-500">This changes the overall font scale used across the tenant portal.</p>
                                @error('font_scale') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/[0.07] bg-[#0f1319] p-5">
                        <p class="text-sm font-semibold text-white">Portal Preview</p>
                        <p class="mt-1 text-sm text-slate-500">This gives a quick idea of how your sidebar identity will look.</p>
                        <div class="mt-5 rounded-2xl border border-white/10 bg-[#0b1120] p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-white/[0.03]">
                                    <template x-if="logoPreview">
                                        <img x-bind:src="logoPreview" alt="Tenant logo preview" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!logoPreview">
                                        <span class="text-lg font-bold text-white">{{ strtoupper(substr(tenant()?->name ?? 'P', 0, 1)) }}</span>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-white">{{ tenant()?->name ?? 'Cooperative' }}</p>
                                    <p class="truncate text-xs uppercase tracking-[0.16em] text-slate-500">{{ request()->getHost() }}</p>
                                </div>
                            </div>
                            <div class="mt-4 rounded-xl px-4 py-3 text-sm font-medium text-white" x-bind:style="{ backgroundColor: selectedAccent === 'green' ? '#22c55e' : selectedAccent === 'blue' ? '#3b82f6' : selectedAccent === 'indigo' ? '#6366f1' : selectedAccent === 'purple' ? '#a855f7' : '#14b8a6' }">Accent preview</div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/[0.07] bg-[#0f1319] p-5">
                        <input type="hidden" name="show_member_photos" value="0">
                        <label for="show_member_photos" class="flex items-start gap-3">
                            <input id="show_member_photos" name="show_member_photos" type="checkbox" value="1" @checked(old('show_member_photos', $settings['show_member_photos'] ?? '0') === '1') class="mt-1 h-4 w-4 rounded border-white/10 bg-white/[0.03] text-emerald-500 focus:ring-emerald-500/40">
                            <span>
                                <span class="block text-sm font-semibold text-white">Show Member Photos</span>
                                <span class="mt-1 block text-sm text-slate-500">Enable member profile images in tenant-facing lists where supported.</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">Save Appearance</button>
            </div>
        </form>
    </div>
    @endif

    @if(! $updatesOnly)
    <div x-cloak x-show="activeTab === 'security'" class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Account Security</p>
                    <h3 class="mt-2 font-heading text-2xl font-bold text-white">Change Password</h3>
                    <p class="mt-2 text-sm text-slate-400">Update your login password here after receiving your temporary credentials.</p>

                    <div class="mt-6 space-y-4">
                        <div class="rounded-2xl border border-white/10 bg-[#0f1319] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Current Account</p>
                            <p class="mt-2 text-sm font-semibold text-white">{{ auth()->user()?->name ?? 'Tenant User' }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $passwordHint }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-[#0f1319] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Best Practice</p>
                            <p class="mt-2 text-sm text-slate-300">Use a strong password that is unique to this tenant workspace. If you ever forget it, you can still use the tenant <span class="font-semibold text-white">Forgot password?</span> flow from the login page.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Password Form</p>
                <h3 class="mt-2 font-heading text-xl font-bold text-white">Update your password</h3>
                <p class="mt-2 text-sm text-slate-400">Enter your current password, then choose a new one for future logins.</p>

                <form method="POST" action="{{ route('settings.password', $tenantParameter, false) }}" class="mt-6 space-y-5" x-data="{ showCurrentPassword: false, showNewPassword: false, showConfirmPassword: false }">
                    @csrf

                    <div>
                        <label for="current_password" class="mb-2 block text-sm font-medium text-slate-200">Current Password</label>
                        <div class="relative">
                            <input id="current_password" name="current_password" x-bind:type="showCurrentPassword ? 'text' : 'password'" autocomplete="current-password" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 pr-12 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            <button type="button" x-on:click="showCurrentPassword = !showCurrentPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-slate-400 transition hover:text-slate-200" x-bind:aria-label="showCurrentPassword ? 'Hide current password' : 'Show current password'">
                                <svg x-cloak x-show="!showCurrentPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                                </svg>
                                <svg x-cloak x-show="showCurrentPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 4.88c6 0 9.75 7.12 9.75 7.12a17.56 17.56 0 0 1-4.13 4.77"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61A17.42 17.42 0 0 0 2.25 12s3.75 7.12 9.75 7.12c1.64 0 3.13-.35 4.46-.94"/>
                                </svg>
                            </button>
                        </div>
                        @error('current_password', 'updatePassword') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-200">New Password</label>
                        <div class="relative">
                            <input id="password" name="password" x-bind:type="showNewPassword ? 'text' : 'password'" autocomplete="new-password" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 pr-12 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            <button type="button" x-on:click="showNewPassword = !showNewPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-slate-400 transition hover:text-slate-200" x-bind:aria-label="showNewPassword ? 'Hide new password' : 'Show new password'">
                                <svg x-cloak x-show="!showNewPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                                </svg>
                                <svg x-cloak x-show="showNewPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 4.88c6 0 9.75 7.12 9.75 7.12a17.56 17.56 0 0 1-4.13 4.77"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61A17.42 17.42 0 0 0 2.25 12s3.75 7.12 9.75 7.12c1.64 0 3.13-.35 4.46-.94"/>
                                </svg>
                            </button>
                        </div>
                        @error('password', 'updatePassword') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-200">Confirm New Password</label>
                        <div class="relative">
                            <input id="password_confirmation" name="password_confirmation" x-bind:type="showConfirmPassword ? 'text' : 'password'" autocomplete="new-password" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 pr-12 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            <button type="button" x-on:click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-slate-400 transition hover:text-slate-200" x-bind:aria-label="showConfirmPassword ? 'Hide confirm password' : 'Show confirm password'">
                                <svg x-cloak x-show="!showConfirmPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                                </svg>
                                <svg x-cloak x-show="showConfirmPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 4.88c6 0 9.75 7.12 9.75 7.12a17.56 17.56 0 0 1-4.13 4.77"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61A17.42 17.42 0 0 0 2.25 12s3.75 7.12 9.75 7.12c1.64 0 3.13-.35 4.46-.94"/>
                                </svg>
                            </button>
                        </div>
                        @error('password_confirmation', 'updatePassword') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                            Save New Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div
        x-cloak
        x-show="activeTab === 'updates'"
        x-data="updateProgress({
            applyUrl: @js(route('settings.updates.apply', $tenantParameter, false)),
            statusUrl: @js(url('/updates/status')),
            csrfToken: @js(csrf_token()),
            latestReleaseId: @js($availableUpdates[0]['id'] ?? null),
            latestVersion: @js($latestVersionLabel),
            updateAvailable: @js($updateAvailable),
            latestReleaseName: @js($releaseName),
        })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Current Version</p>
                <p class="mt-3 font-heading text-3xl font-bold text-white">{{ $currentVersionLabel }}</p>
                <p class="mt-2 text-sm text-slate-500">Installed version from this tenant workspace.</p>
            </div>
            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Latest Available</p>
                <p class="mt-3 font-heading text-3xl font-bold {{ $updateAvailable ? 'text-yellow-300' : 'text-emerald-300' }}">{{ $latestVersionLabel }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $releaseName }}</p>
            </div>
            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status</p>
                <p class="mt-3 font-heading text-3xl font-bold {{ $updateAvailable ? 'text-yellow-300' : 'text-emerald-300' }}">{{ $updateAvailable ? 'Update Available' : 'Up to Date' }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $updateAvailable ? ($availableUpdateCount === 1 ? '1 release is ready to review below.' : $availableUpdateCount.' releases are ready to review below.') : 'This tenant is running the latest published release.' }}</p>
            </div>
        </div>

        <template x-if="state === 'idle'">
            <div class="space-y-6">
                @if($updateAvailable)
                    <div class="rounded-2xl border border-yellow-500/20 bg-yellow-500/5 p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-yellow-300">Release: {{ $releaseName }}</p>
                                <p class="mt-1 text-xs text-slate-500">Published: {{ $releasePublishedLabel }}</p>

                                <div class="mt-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Changelog</p>
                                    <ul class="mt-3 space-y-2 text-sm text-slate-300">
                                        @forelse($changelogItems as $item)
                                            <li class="flex gap-3">
                                                <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-yellow-300"></span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @empty
                                            <li class="text-slate-500">No changelog details available.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>

                            <div class="settings-update-panel flex w-full max-w-sm flex-col gap-3 rounded-2xl border p-5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full border border-yellow-500/20 bg-yellow-500/10 text-yellow-300">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.25 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="settings-update-heading text-sm font-semibold">Update ready</p>
                                        <p class="settings-update-muted text-xs" x-text="latestReleaseName"></p>
                                    </div>
                                </div>

                                <p class="settings-update-body text-sm leading-6">Start the tenant update when you are ready. The interface will stay on this page and show each deployment stage as it runs.</p>

                                <div class="flex flex-wrap gap-3">
                                    @if(!empty($availableUpdates) && $canManageSettings)
                                        <button
                                            type="button"
                                            x-on:click="startUpdate(latestReleaseId)"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-70"
                                            x-bind:disabled="!latestReleaseId"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v11.25m0 0 4.5-4.5m-4.5 4.5-4.5-4.5M4.5 18.75h15" />
                                            </svg>
                                            Update Now
                                        </button>
                                    @elseif(!empty($availableUpdates))
                                        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                                            You can review this release, but only users with update access can apply it.
                                        </div>
                                    @endif

                                    @if($releaseUrl !== '')
                                        <a href="{{ $releaseUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:text-white">
                                            View Full Release Notes
                                        </a>
                                    @endif

                                    @if($canManageSettings)
                                        <form method="POST" action="{{ route('settings.updates.sync', $tenantParameter, false) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:text-white">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                                Sync Releases
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($availableUpdateCount > 1)
                        <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Available Updates</p>
                                    <h3 class="mt-2 font-heading text-xl font-bold text-white">{{ $availableUpdateCount }} releases available</h3>
                                    <p class="mt-2 text-sm text-slate-400">The newest release is highlighted above. Older pending releases are listed here for review.</p>
                                </div>
                                <div class="flex flex-col items-start gap-3 md:items-end">
                                    <p class="text-xs text-slate-500">Only the latest release can be applied from this screen.</p>
                                    <button
                                        type="button"
                                        x-on:click="availableUpdatesOpen = !availableUpdatesOpen"
                                        class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-[#0f1319] px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:text-white"
                                    >
                                        <span x-text="availableUpdatesOpen ? 'Hide Available Updates' : 'Show Available Updates'"></span>
                                        <svg class="h-4 w-4 transition-transform duration-200" x-bind:class="availableUpdatesOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div x-cloak x-show="availableUpdatesOpen" x-transition.opacity.duration.200ms class="mt-6 space-y-4">
                                @foreach($availableUpdates as $index => $release)
                                    @php
                                        $releaseTag = (string) ($release['tag'] ?? $release['version'] ?? 'Unknown');
                                        $releaseTitle = (string) ($release['title'] ?? 'Untitled release');
                                        $releasePublished = filled($release['published_at'] ?? null)
                                            ? \Illuminate\Support\Carbon::parse((string) $release['published_at'])->format('M d, Y h:i A')
                                            : 'Unknown';
                                        $releaseChangelogItems = $release['changelog_items'] ?? [];
                                    @endphp

                                    <div class="rounded-2xl border border-white/10 bg-[#0f1319] p-5">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-200">{{ $releaseTag }}</span>
                                                    @if($index === 0)
                                                        <span class="inline-flex rounded-full border border-yellow-500/30 bg-yellow-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-yellow-300">Latest</span>
                                                    @endif
                                                    @if($release['is_required'] ?? false)
                                                        <span class="inline-flex rounded-full border border-red-500/30 bg-red-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-red-300">Required</span>
                                                    @elseif($release['is_stable'] ?? false)
                                                        <span class="inline-flex rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-300">Stable</span>
                                                    @endif
                                                </div>

                                                <h4 class="mt-3 text-lg font-semibold text-white">{{ $releaseTitle }}</h4>
                                                <p class="mt-1 text-xs text-slate-500">Published {{ $releasePublished }}</p>
                                            </div>

                                            @if(($release['release_url'] ?? '') !== '')
                                                <a href="{{ $release['release_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:text-white">
                                                    View Release Notes
                                                </a>
                                            @endif
                                        </div>

                                        <div class="mt-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Changelog</p>
                                            <ul class="mt-3 space-y-2 text-sm text-slate-300">
                                                @forelse($releaseChangelogItems as $item)
                                                    <li class="flex gap-3">
                                                        <span class="mt-1.5 h-1.5 w-1.5 rounded-full {{ $index === 0 ? 'bg-yellow-300' : 'bg-emerald-300' }}"></span>
                                                        <span>{{ $item }}</span>
                                                    </li>
                                                @empty
                                                    <li class="text-slate-500">No changelog details available.</li>
                                                @endforelse
                                            </ul>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-emerald-300">PayMonitor is up to date.</p>
                                <p class="mt-1 text-sm text-slate-400">When a new release arrives, you can apply it from here and watch the progress live.</p>
                            </div>
                            @if($canManageSettings)
                                <form method="POST" action="{{ route('settings.updates.sync', $tenantParameter, false) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/30 px-4 py-2 text-sm font-medium text-emerald-300 transition hover:bg-emerald-500/10">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                        Check for Updates
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </template>

        <template x-if="state === 'updating'">
            <div class="settings-update-panel rounded-2xl border p-6 shadow-[0_24px_80px_rgba(0,0,0,0.18)]">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400">
                            <svg class="h-7 w-7 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 12.73-5.303M19.5 12a7.5 7.5 0 0 1-12.73 5.303" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75h.75V7.5M7.5 20.25h-.75V16.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="settings-update-muted text-xs font-semibold uppercase tracking-[0.18em]">Tenant Update</p>
                            <h3 class="settings-update-heading mt-2 text-2xl font-bold">Updating to <span class="text-emerald-500" x-text="version || latestVersion || 'latest release'"></span></h3>
                            <p class="settings-update-muted mt-1 text-sm">Elapsed: <span class="font-mono settings-update-body" x-text="formatElapsed(elapsedSeconds)"></span></p>
                        </div>
                    </div>
                    <div class="settings-update-surface rounded-xl border px-4 py-3 text-sm">
                        <p class="settings-update-muted font-mono text-xs uppercase tracking-[0.14em]">Current Stage</p>
                        <p class="settings-update-heading mt-2 font-medium" x-text="statusMessage || 'Preparing update...'"></p>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="mb-3 flex items-center justify-between gap-4">
                        <p class="settings-update-body text-sm font-semibold">Progress</p>
                        <p class="font-mono text-sm text-emerald-500" x-text="`${progress}%`"></p>
                    </div>
                    <div class="settings-update-surface h-4 w-full overflow-hidden rounded-full border">
                        <div class="h-full rounded-full bg-gradient-to-r from-green-600 to-green-400 transition-all duration-700 ease-out" x-bind:style="`width: ${progress}%`"></div>
                    </div>
                </div>

                <div class="mt-8 overflow-x-auto">
                    <div class="min-w-[760px]">
                        <div class="flex items-center gap-3">
                            <template x-for="(step, index) in steps" :key="step.key">
                                <div class="flex flex-1 items-center gap-3">
                                    <div class="flex flex-col items-center">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold transition-all duration-500" x-bind:class="stepCircleClass(index + 1)">
                                            <template x-if="isStepCompleted(index + 1)">
                                                <svg class="h-5 w-5 settings-scale-in" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                                                </svg>
                                            </template>
                                            <template x-if="!isStepCompleted(index + 1)">
                                                <span x-text="index + 1"></span>
                                            </template>
                                        </div>
                                        <p class="mt-2 text-center text-xs" x-bind:class="stepLabelClass(index + 1)" x-text="step.label"></p>
                                    </div>
                                    <template x-if="index < steps.length - 1">
                                        <div class="h-0.5 flex-1 rounded-full transition-colors duration-500" x-bind:class="stepConnectorClass(index + 1)"></div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <p class="settings-update-subtle mb-2 text-xs uppercase tracking-[0.18em]">Process Log</p>
                    <div id="process-log" x-ref="processLog" class="h-48 overflow-y-auto rounded-xl border border-[#21262d] bg-[#0d1117] p-4 font-mono text-xs shadow-inner">
                        <template x-if="logEntries.length === 0">
                            <p class="text-slate-500">Waiting for update logs...</p>
                        </template>
                        <template x-for="(entry, index) in logEntries" :key="`${entry.time}-${entry.message}-${index}`">
                            <div class="settings-log-line flex items-start gap-3 leading-6">
                                <span class="shrink-0 text-slate-500" x-text="`[${entry.time}]`"></span>
                                <span x-bind:class="entry.color" x-text="entry.message"></span>
                                <template x-if="index === logEntries.length - 1">
                                    <span class="animate-pulse text-green-400">▋</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-yellow-500/20 bg-yellow-500/5 p-3 text-sm text-yellow-100">
                    <p class="font-semibold">Warning</p>
                    <p class="settings-update-body mt-1 leading-6">Do not close or refresh this page. Update is running in background. Estimated time: 2-5 minutes.</p>
                </div>
            </div>
        </template>

        <template x-if="state === 'completed'">
            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-6">
                <div class="mx-auto max-w-3xl text-center">
                    <div class="settings-scale-in mx-auto flex h-20 w-20 items-center justify-center rounded-full border-2 border-emerald-500 bg-emerald-500/10 text-emerald-500">
                        <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="settings-update-heading mt-6 text-xl font-bold">Update Successful!</h3>
                    <p class="settings-update-body mt-2 text-sm">Updated to <span class="font-semibold text-emerald-500" x-text="version || latestVersion"></span></p>
                    <p class="settings-update-muted mt-2 text-sm">Completed in <span class="font-medium settings-update-body" x-text="formatElapsedDetailed(elapsedSeconds)"></span></p>

                    <div class="mt-8 rounded-xl border border-[#21262d] bg-[#0d1117] p-4 text-left shadow-inner">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Recent Update Log</p>
                        <div class="mt-3 space-y-2 font-mono text-xs">
                            <template x-for="entry in recentLogEntries()" :key="`${entry.time}-${entry.message}`">
                                <div class="flex items-start gap-3">
                                    <span class="shrink-0 text-slate-500" x-text="`[${entry.time}]`"></span>
                                    <span x-bind:class="entry.color" x-text="entry.message"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-8">
                        <p class="settings-update-muted text-sm">Reloading in <span class="font-semibold settings-update-heading" x-text="reloadCountdown"></span> seconds...</p>
                        <div class="settings-update-surface mx-auto mt-3 h-2 w-full max-w-md overflow-hidden rounded-full border">
                            <div class="h-full rounded-full bg-gradient-to-r from-green-600 to-green-400 transition-all duration-700 ease-linear" x-bind:style="`width: ${((5 - reloadCountdown) / 5) * 100}%`"></div>
                        </div>
                        <button type="button" x-on:click="reloadNow()" class="mt-4 inline-flex items-center rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                            Reload Now
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="state === 'failed'">
            <div class="rounded-2xl border border-red-500/20 bg-red-500/5 p-6">
                <div class="mx-auto max-w-4xl">
                    <div class="text-center">
                        <div class="settings-scale-in mx-auto flex h-20 w-20 items-center justify-center rounded-full border-2 border-red-500 bg-red-500/10 text-red-500">
                            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-red-500">Update Failed</h3>
                        <p class="settings-update-body mt-2 text-sm">The update did not complete. Review the error details and log below.</p>
                    </div>

                    <div class="mt-8 rounded-lg border border-red-500/30 bg-[#0d1117] p-4 font-mono text-xs text-red-500">
                        <p class="uppercase tracking-[0.14em] text-red-500/70">Error Details</p>
                        <p class="mt-3 whitespace-pre-wrap leading-6" x-text="errorMessage || 'Unknown update error.'"></p>
                    </div>

                    <div class="mt-6 rounded-xl border border-[#21262d] bg-[#0d1117] p-4 shadow-inner">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Full Process Log</p>
                        <div class="mt-3 h-56 space-y-2 overflow-y-auto font-mono text-xs">
                            <template x-if="logEntries.length === 0">
                                <p class="text-slate-500">No log entries available.</p>
                            </template>
                            <template x-for="(entry, index) in logEntries" :key="`${entry.time}-${entry.message}-${index}`">
                                <div class="flex items-start gap-3">
                                    <span class="shrink-0 text-slate-500" x-text="`[${entry.time}]`"></span>
                                    <span x-bind:class="entry.color" x-text="entry.message"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <button type="button" x-on:click="resetUpdate()" class="inline-flex items-center rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-white/20 hover:text-white">
                            Try Again
                        </button>
                        <button type="button" x-on:click="copyLogWithFeedback()" class="inline-flex items-center rounded-xl bg-red-500/10 px-4 py-2 text-sm font-medium text-red-300 transition hover:bg-red-500/20">
                            <span x-text="copiedLog ? 'Copied!' : 'Copy Log'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <section class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Audit Trail</p>
                    <h3 class="mt-2 text-2xl font-semibold text-white">Update History</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        {{ $historyCount > 0 ? $historyCount.' update attempt'.($historyCount === 1 ? '' : 's').' recorded.' : 'No updates applied yet.' }}
                    </p>
                </div>

                <button
                    type="button"
                    x-on:click="historyOpen = !historyOpen"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-[#0f1319] px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:text-white"
                >
                    <span x-text="historyOpen ? 'Hide Update History' : 'Show Update History'"></span>
                    <svg class="h-4 w-4 transition-transform duration-200" x-bind:class="historyOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                    </svg>
                </button>
            </div>

            <div x-cloak x-show="historyOpen" x-transition.opacity.duration.200ms class="mt-6">
                @if($historyCount === 0)
                    <p class="text-sm text-slate-500">No updates applied yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm text-slate-300">
                            <thead>
                                <tr class="border-b border-white/[0.07] text-xs uppercase tracking-[0.16em] text-slate-500">
                                    <th class="px-4 py-3">Version</th>
                                    <th class="px-4 py-3">Attempted At</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($updateHistory as $entry)
                                    @php
                                        $attemptedAt = $entry->applied_at ?? $entry->created_at;
                                        $status = (string) ($entry->status ?? 'unknown');
                                        $entryNote = $entry->failure_reason ?: (data_get($entry->metadata, 'updated_by') !== null
                                            ? 'Updated by user #'.data_get($entry->metadata, 'updated_by')
                                            : 'Applied successfully');
                                        $statusClass = match ($status) {
                                            'updated' => 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                                            'failed' => 'border border-red-500/30 bg-red-500/10 text-red-300',
                                            default => 'border border-yellow-500/30 bg-yellow-500/10 text-yellow-300',
                                        };
                                    @endphp
                                    <tr class="border-b border-white/[0.07] transition hover:bg-white/[0.02]">
                                        <td class="px-4 py-4 font-semibold text-white">
                                            {{ $entry->appRelease?->tag ?? 'Unknown' }}
                                            @if($entry->is_current)
                                                <span class="ml-2 inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-300">Current</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-slate-400">{{ $attemptedAt?->format('M d, Y h:i A') ?? '-' }}</td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] {{ $statusClass }}">
                                                {{ str_replace('_', ' ', $status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-slate-400">
                                            {{ $entryNote }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>

    @if(! $updatesOnly)
    <div x-cloak x-show="activeTab === 'support'" class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Contact Support</p>
                    <h3 class="mt-2 font-heading text-2xl font-bold text-white">Need help from PayMonitor?</h3>
                    <p class="mt-2 text-sm text-slate-400">Use the details below for account, billing, technical, or update-related concerns.</p>

                    <div class="mt-6 space-y-4">
                        <div class="rounded-2xl border border-white/10 bg-[#0f1319] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Support Email</p>
                            <p class="mt-2 text-sm font-semibold text-white">{{ $supportEmail }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-[#0f1319] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Support Phone</p>
                            <p class="mt-2 text-sm font-semibold text-white">{{ $supportPhone }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-[#0f1319] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Support Hours</p>
                            <p class="mt-2 text-sm font-semibold text-white">{{ $supportHours }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Updates Included</p>
                    <h3 class="mt-2 font-heading text-xl font-bold text-white">Support and Updates</h3>
                    <ul class="mt-4 space-y-3 text-sm text-slate-300">
                        <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-emerald-400"></span><span>Version announcements and changelog tracking</span></li>
                        <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-emerald-400"></span><span>Tenant release notifications and changelog visibility</span></li>
                        <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-emerald-400"></span><span>Billing and account assistance through support requests</span></li>
                        <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-emerald-400"></span><span>Technical issue reporting for portal and access concerns</span></li>
                    </ul>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Submit Request</p>
                    <h3 class="mt-2 font-heading text-xl font-bold text-white">Send a support request</h3>
                    <p class="mt-2 text-sm text-slate-400">This request is saved in the central app and emailed to the support contact.</p>

                    <form method="POST" action="{{ route('settings.support', $tenantParameter, false) }}" class="mt-6 space-y-5">
                        @csrf
                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                            <div class="lg:col-span-2">
                                <label for="subject" class="mb-2 block text-sm font-medium text-slate-200">Subject</label>
                                <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]" placeholder="Describe your concern" required>
                                @error('subject') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="min-w-0">
                                <label for="category" class="mb-2 block text-sm font-medium text-slate-200">Category</label>
                                <select id="category" name="category" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                                    @foreach(['general' => 'General', 'technical' => 'Technical', 'billing' => 'Billing', 'account' => 'Account', 'feature' => 'Feature Request'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="min-w-0 rounded-2xl border border-white/10 bg-[#0f1319] px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Requester</p>
                                <p class="mt-2 truncate text-sm font-semibold text-white" title="{{ auth()->user()?->name ?? tenant()?->admin_name ?? tenant()?->name }}">
                                    {{ auth()->user()?->name ?? tenant()?->admin_name ?? tenant()?->name }}
                                </p>
                                <p class="mt-1 break-all text-xs leading-5 text-slate-500 sm:text-sm">
                                    {{ auth()->user()?->email ?? tenant()?->email }}
                                </p>
                            </div>

                            <div class="lg:col-span-2">
                                <label for="message" class="mb-2 block text-sm font-medium text-slate-200">Message</label>
                                <textarea id="message" name="message" rows="6" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]" placeholder="Explain the issue, what happened, and what you need help with." required>{{ old('message') }}</textarea>
                                @error('message') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                            Submit Support Request
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6" x-data="{ requestsOpen: false }">
                    <div class="flex flex-col gap-2 border-b border-white/[0.06] pb-5 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Request History</p>
                            <h3 class="mt-2 font-heading text-xl font-bold text-white">Recent support requests</h3>
                            <p class="mt-2 text-sm text-slate-400">Track the concerns this tenant has already submitted.</p>
                        </div>
                        <div class="flex flex-col items-start gap-3 md:items-end">
                            <button
                                type="button"
                                x-on:click="requestsOpen = !requestsOpen"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-[#0f1319] px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:text-white"
                            >
                                <span x-text="requestsOpen ? 'Hide Requests' : 'Show Requests'"></span>
                                <svg class="h-4 w-4 transition-transform duration-200" x-bind:class="requestsOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div x-cloak x-show="requestsOpen" x-transition.opacity.duration.200ms class="mt-5 space-y-4">
                        @forelse($supportRequests as $supportRequest)
                            <div class="rounded-2xl border border-white/10 bg-[#0f1319] p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-white">{{ $supportRequest->subject }}</p>
                                        <p class="mt-1 text-sm text-slate-400">{{ ucfirst($supportRequest->category) }} request from {{ $supportRequest->requester_name }}</p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $supportRequest->status === 'open' ? 'settings-support-status-open' : ($supportRequest->status === 'resolved' ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border border-blue-500/20 bg-blue-500/10 text-blue-300') }}">
                                        {{ str_replace('_', ' ', $supportRequest->status) }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-300">{{ \Illuminate\Support\Str::limit($supportRequest->message, 220) }}</p>
                                
                                @if($supportRequest->responses->isNotEmpty())
                                    <div class="mt-4 space-y-3 border-t border-white/[0.06] pt-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-400">{{ $supportRequest->responses->count() }} Response(s) from Support</p>
                                        @foreach($supportRequest->responses as $response)
                                            <div class="rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-3">
                                                <div class="mb-2 flex items-start justify-between gap-2">
                                                    <p class="text-xs font-semibold text-emerald-300">{{ $response->responder_name }}</p>
                                                    <p class="text-xs text-slate-500">{{ $response->created_at->format('M d, Y h:i A') }}</p>
                                                </div>
                                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-200">{{ $response->message }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                                    <span>Submitted {{ $supportRequest->created_at?->format('M d, Y h:i A') }}</span>
                                    @if($supportRequest->resolved_at)
                                        <span>Resolved {{ $supportRequest->resolved_at->format('M d, Y h:i A') }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/[0.08] bg-[#0f1319] px-5 py-10 text-center">
                                <p class="text-sm text-slate-500">No support requests submitted yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function updateProgress(config) {
    return {
        state: 'idle',
        progress: 0,
        currentStep: 0,
        totalSteps: 7,
        currentStage: '',
        statusMessage: '',
        version: config.latestVersion || '',
        latestVersion: config.latestVersion || '',
        latestReleaseId: config.latestReleaseId || null,
        latestReleaseName: config.latestReleaseName || '',
        errorMessage: '',
        logEntries: [],
        startTime: null,
        elapsedSeconds: 0,
        elapsedTimer: null,
        pollInterval: null,
        reloadCountdown: 5,
        copiedLog: false,
        applyUrl: config.applyUrl,
        statusUrl: config.statusUrl,
        csrfToken: config.csrfToken,
        updateAvailable: !!config.updateAvailable,
        steps: [
            { key: 'download', label: 'Download', stages: ['start', 'download', 'preflight'] },
            { key: 'extract', label: 'Extract', stages: ['extract', 'backup-db'] },
            { key: 'backup', label: 'Backup', stages: ['backup'] },
            { key: 'deploy', label: 'Deploy', stages: ['swap', 'deploy'] },
            { key: 'composer', label: 'Composer', stages: ['composer'] },
            { key: 'migrate', label: 'Migrate', stages: ['migrate', 'finalize'] },
            { key: 'cache', label: 'Cache', stages: ['cache', 'queue'] },
        ],

        init() {
            this.checkStatus();
        },

        getStepIndex(stage) {
            for (let i = 0; i < this.steps.length; i++) {
                if (this.steps[i].stages.includes(stage)) {
                    return i + 1;
                }
            }

            return 0;
        },

        getLogColor(stage) {
            const colors = {
                start: 'text-blue-400',
                download: 'text-blue-400',
                preflight: 'text-blue-400',
                extract: 'text-slate-400',
                'backup-db': 'text-slate-400',
                backup: 'text-slate-400',
                swap: 'text-yellow-400',
                deploy: 'text-yellow-400',
                composer: 'text-purple-400',
                npm: 'text-purple-400',
                migrate: 'text-orange-400',
                finalize: 'text-green-400',
                cache: 'text-green-400',
                queue: 'text-green-400',
                rollback: 'text-red-400',
                failed: 'text-red-400',
                error: 'text-red-400',
            };

            return colors[stage] || 'text-slate-400';
        },

        formatTime(isoString) {
            if (!isoString) {
                return '';
            }

            const date = new Date(isoString);

            return date.toLocaleTimeString('en-PH', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        },

        formatElapsed(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainder = seconds % 60;

            return minutes > 0 ? `${minutes}m ${remainder}s` : `${remainder}s`;
        },

        formatElapsedDetailed(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainder = seconds % 60;

            if (minutes <= 0) {
                return `${remainder} second${remainder === 1 ? '' : 's'}`;
            }

            return `${minutes} minute${minutes === 1 ? '' : 's'} ${remainder} second${remainder === 1 ? '' : 's'}`;
        },

        startElapsedTimer() {
            if (this.elapsedTimer) {
                return;
            }

            this.startTime = Date.now() - (this.elapsedSeconds * 1000);
            this.elapsedTimer = setInterval(() => {
                this.elapsedSeconds = Math.floor((Date.now() - this.startTime) / 1000);
            }, 1000);
        },

        stopElapsedTimer() {
            if (this.elapsedTimer) {
                clearInterval(this.elapsedTimer);
                this.elapsedTimer = null;
            }
        },

        isStepCompleted(stepNumber) {
            return stepNumber < this.currentStep || (this.state === 'completed' && stepNumber <= this.totalSteps);
        },

        stepCircleClass(stepNumber) {
            if (this.isStepCompleted(stepNumber)) {
                return 'bg-emerald-500 text-white border-2 border-emerald-500 scale-100 shadow-md shadow-emerald-500/20';
            }

            if (stepNumber === this.currentStep && this.state === 'updating') {
                return 'bg-amber-500 text-white border-2 border-amber-500 animate-pulse shadow-md shadow-amber-500/20';
            }

            return 'settings-step-pending';
        },

        stepLabelClass(stepNumber) {
            if (this.isStepCompleted(stepNumber)) {
                return 'text-emerald-500 font-medium';
            }

            if (stepNumber === this.currentStep && this.state === 'updating') {
                return 'text-amber-500 font-medium';
            }

            return 'settings-update-muted';
        },

        stepConnectorClass(stepNumber) {
            return this.isStepCompleted(stepNumber + 1) || (this.state === 'completed' && stepNumber < this.totalSteps)
                ? 'bg-emerald-500'
                : 'settings-step-line';
        },

        recentLogEntries() {
            return this.logEntries.slice(-5);
        },

        async startUpdate(releaseId) {
            if (!releaseId || !this.applyUrl) {
                this.showFailed('No release is available to start this update.');
                return;
            }

            this.state = 'updating';
            this.progress = 0;
            this.currentStep = 0;
            this.currentStage = '';
            this.statusMessage = 'Starting update...';
            this.errorMessage = '';
            this.logEntries = [];
            this.elapsedSeconds = 0;
            this.version = this.latestVersion || '';
            this.startElapsedTimer();

            try {
                const response = await fetch(this.applyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({
                        release_id: String(releaseId),
                    }),
                });

                let data = null;

                try {
                    data = await response.clone().json();
                } catch (error) {
                    data = null;
                }

                if (data && data.success === false) {
                    this.stopElapsedTimer();
                    this.showFailed(data.message || 'Failed to start update.');
                    return;
                }

                if (data && data.version) {
                    this.version = data.version;
                }

                this.startPolling();
            } catch (error) {
                this.stopElapsedTimer();
                this.showFailed('Network error: ' + error.message);
            }
        },

        startPolling() {
            if (this.pollInterval) {
                return;
            }

            this.pollInterval = setInterval(() => {
                this.checkStatus();
            }, 3000);
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        async checkStatus() {
            if (!this.statusUrl) {
                return;
            }

            try {
                const response = await fetch(this.statusUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                if (!data || data.state === 'idle') {
                    return;
                }

                this.progress = data.progress || 0;
                this.currentStage = data.stage || '';
                this.statusMessage = data.message || '';
                this.currentStep = this.getStepIndex(data.stage || '');
                this.version = data.version || this.version || this.latestVersion;

                if (Array.isArray(data.history)) {
                    this.logEntries = data.history.map((entry) => ({
                        time: this.formatTime(entry.at),
                        message: entry.message,
                        stage: entry.stage,
                        color: this.getLogColor(entry.stage),
                    }));

                    this.$nextTick(() => {
                        const log = this.$refs.processLog;
                        if (log) {
                            log.scrollTop = log.scrollHeight;
                        }
                    });
                }

                if (data.state === 'running' && this.state !== 'updating') {
                    this.state = 'updating';
                }

                if (data.state === 'running') {
                    this.startElapsedTimer();
                    this.startPolling();
                }

                if (data.state === 'completed') {
                    this.stopPolling();
                    this.stopElapsedTimer();
                    this.progress = 100;
                    this.currentStep = this.totalSteps;
                    this.statusMessage = data.message || 'Update complete.';

                    setTimeout(() => {
                        this.state = 'completed';
                        this.startReloadCountdown();
                    }, 800);
                }

                if (data.state === 'failed') {
                    this.stopPolling();
                    this.stopElapsedTimer();
                    this.showFailed(data.error || 'Update failed.');
                }
            } catch (error) {
                console.warn('Status poll error:', error);
            }
        },

        showFailed(message) {
            this.state = 'failed';
            this.errorMessage = message;
        },

        resetUpdate() {
            this.stopPolling();
            this.stopElapsedTimer();
            this.state = 'idle';
            this.progress = 0;
            this.currentStep = 0;
            this.currentStage = '';
            this.statusMessage = '';
            this.errorMessage = '';
            this.logEntries = [];
            this.elapsedSeconds = 0;
            this.reloadCountdown = 5;
            this.copiedLog = false;
            this.version = this.latestVersion || '';
        },

        startReloadCountdown() {
            this.reloadCountdown = 5;
            const timer = setInterval(() => {
                this.reloadCountdown--;

                if (this.reloadCountdown <= 0) {
                    clearInterval(timer);
                    this.reloadNow();
                }
            }, 1000);
        },

        reloadNow() {
            window.location.href = window.location.pathname + '?updated=1&t=' + Date.now();
        },

        async copyLog() {
            const text = this.logEntries.map((entry) => `[${entry.time}] ${entry.message}`).join('\n');

            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (error) {
                return false;
            }
        },

        async copyLogWithFeedback() {
            this.copiedLog = await this.copyLog();

            if (this.copiedLog) {
                setTimeout(() => {
                    this.copiedLog = false;
                }, 2000);
            }
        },
    };
}
</script>
@endpush
