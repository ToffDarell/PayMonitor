@extends('layouts.tenant')

@section('title', 'Settings')

@php
    $tenantParameter = ['tenant' => tenant()?->id ?? request()->route('tenant')];
    $logoPath = $settings['logo_path'] ?? null;
    $logoUrl = filled($logoPath)
        ? route('stancl.tenancy.asset', ['path' => ltrim((string) $logoPath, '/')], false)
        : null;
    $updateAvailable = (bool) ($updateInfo['update_available'] ?? false);
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
</style>
@endpush

@section('content')
<div
    x-data="{
        activeTab: @js($activeTab),
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
            <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Tenant Settings</h2>
            <p class="mt-1 text-sm text-slate-400">Customize your portal details, appearance, and release update visibility.</p>
        </div>
        <div class="flex flex-wrap gap-2 rounded-2xl border border-white/[0.07] bg-white/[0.02] p-2">
            <button type="button" x-on:click="activeTab = 'general'" x-bind:class="activeTab === 'general' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">General</button>
            <button type="button" x-on:click="activeTab = 'appearance'" x-bind:class="activeTab === 'appearance' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Appearance</button>
            <button type="button" x-on:click="activeTab = 'updates'" x-bind:class="activeTab === 'updates' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Updates</button>
            <button type="button" x-on:click="activeTab = 'support'" x-bind:class="activeTab === 'support' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Support</button>
        </div>
    </div>

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
                    <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" placeholder="support@{{ request()->getHost() }}" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
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

            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
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

                <div class="space-y-4">
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

    <div x-cloak x-show="activeTab === 'updates'" class="space-y-6">
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
                <p class="mt-2 text-sm text-slate-500">{{ $updateAvailable ? 'A new release is ready to install by your PayMonitor administrator.' : 'This tenant is running the latest published release.' }}</p>
            </div>
        </div>

        @if($updateAvailable)
            <div class="rounded-2xl border border-yellow-500/20 bg-yellow-500/5 p-6">
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

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    @if($releaseUrl !== '')
                        <a href="{{ $releaseUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:text-white">
                            View Full Release Notes
                        </a>
                    @endif
                    <p class="text-sm text-slate-400">To install updates, please contact your PayMonitor administrator.</p>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5">
                <p class="text-sm font-semibold text-emerald-300">PayMonitor is up to date.</p>
                <p class="mt-1 text-sm text-slate-400">To install future updates, please contact your PayMonitor administrator.</p>
            </div>
        @endif
    </div>

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
                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="subject" class="mb-2 block text-sm font-medium text-slate-200">Subject</label>
                                <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]" placeholder="Describe your concern" required>
                                @error('subject') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="category" class="mb-2 block text-sm font-medium text-slate-200">Category</label>
                                <select id="category" name="category" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                                    @foreach(['general' => 'General', 'technical' => 'Technical', 'billing' => 'Billing', 'account' => 'Account', 'feature' => 'Feature Request'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-[#0f1319] px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Requester</p>
                                <p class="mt-2 text-sm font-semibold text-white">{{ auth()->user()?->name ?? tenant()?->admin_name ?? tenant()?->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ auth()->user()?->email ?? tenant()?->email }}</p>
                            </div>

                            <div class="md:col-span-2">
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

                <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                    <div class="border-b border-white/[0.06] pb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Request History</p>
                        <h3 class="mt-2 font-heading text-xl font-bold text-white">Recent support requests</h3>
                        <p class="mt-2 text-sm text-slate-400">Track the concerns this tenant has already submitted.</p>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse($supportRequests as $supportRequest)
                            <div class="rounded-2xl border border-white/10 bg-[#0f1319] p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-white">{{ $supportRequest->subject }}</p>
                                        <p class="mt-1 text-sm text-slate-400">{{ ucfirst($supportRequest->category) }} request from {{ $supportRequest->requester_name }}</p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $supportRequest->status === 'open' ? 'settings-support-status-open' : 'border border-white/10 bg-white/[0.03] text-slate-400' }}">
                                        {{ $supportRequest->status }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-300">{{ \Illuminate\Support\Str::limit($supportRequest->message, 220) }}</p>
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
</div>
@endsection
