@extends('layouts.tenant')

@section('title', 'Settings')

@php
    $tenantParameter = ['tenant' => tenant()?->id ?? request()->route('tenant')];
    $activeTab = old('_active_tab', $activeTab ?? 'general');
    $settings = $settings ?? [];
    $supportContact = $supportContact ?? [];
    $supportRequests = $supportRequests ?? collect();
    $allErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;

    $logoPath = $settings['logo_path'] ?? null;
    $logoUrl = filled($logoPath)
        ? route('stancl.tenancy.asset', ['path' => ltrim((string) $logoPath, '/')], false)
        : null;
    $supportEmail = $supportContact['email'] ?? config('mail.from.address', 'support@paymonitor.test');
    $supportPhone = $supportContact['phone'] ?? '+63 917 000 0000';
    $supportHours = $supportContact['hours'] ?? 'Mon-Fri, 8:00 AM - 5:00 PM';
    $supportPrefillSubject = (string) request()->query('support_subject', '');
    $supportPrefillCategory = (string) request()->query('support_category', 'general');
    $supportPrefillMessage = (string) request()->query('support_message', '');
    $passwordHint = auth()->user()?->email ?? tenant()?->email ?? 'your account email';
    $canViewSettings = auth()->user()?->hasTenantPermission(\App\Support\TenantPermissions::SETTINGS_VIEW) ?? false;
    $canManageSettings = auth()->user()?->hasTenantPermission(\App\Support\TenantPermissions::SETTINGS_UPDATE) ?? false;
    $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;
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
        from { transform: scale(0.85); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    @keyframes settingsFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .settings-scale-in {
        animation: settingsScaleIn 320ms ease-out both;
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
            <p class="mt-1 text-sm text-slate-400">Customize your portal details, appearance, account security, and more.</p>
        </div>
        <div class="flex flex-wrap gap-2 rounded-2xl border border-white/[0.07] bg-white/[0.02] p-2">
            <button type="button" x-on:click="activeTab = 'general'" x-bind:class="activeTab === 'general' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">General</button>
            <button type="button" x-on:click="activeTab = 'appearance'" x-bind:class="activeTab === 'appearance' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Appearance</button>
            <button type="button" x-on:click="activeTab = 'security'" x-bind:class="activeTab === 'security' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Security</button>
            <button type="button" x-on:click="activeTab = 'support'" x-bind:class="activeTab === 'support' ? 'settings-tab-active' : 'settings-tab-default'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Support</button>
        </div>
    </div>

    {{-- GENERAL TAB --}}
    <div x-cloak x-show="activeTab === 'general'" class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
        <form method="POST" action="{{ route('settings.update', $tenantParameter, false) }}" class="space-y-8">
            @csrf
            <input type="hidden" name="active_tab" value="general">

            <div class="space-y-6">
                <div>
                    <p class="text-sm font-semibold text-white">Basic Information</p>
                    <p class="mt-1 text-sm text-slate-500">Update the portal name, email, and contact information for this tenant workspace.</p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="portal_name" class="mb-2 block text-sm font-medium text-slate-200">Portal Name</label>
                        <input id="portal_name" name="portal_name" type="text" value="{{ old('portal_name', $settings['portal_name'] ?? tenant()?->name ?? '') }}" placeholder="e.g. Bayanihan Credit Cooperative" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                        @error('portal_name') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="portal_email" class="mb-2 block text-sm font-medium text-slate-200">Portal Email</label>
                        <input id="portal_email" name="portal_email" type="email" value="{{ old('portal_email', $settings['portal_email'] ?? tenant()?->email ?? '') }}" placeholder="admin@cooperative.example.com" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                        @error('portal_email') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="cooperative_tagline" class="mb-2 block text-sm font-medium text-slate-200">Tagline</label>
                    <input id="cooperative_tagline" name="cooperative_tagline" type="text" value="{{ old('cooperative_tagline', $settings['cooperative_tagline'] ?? '') }}" placeholder="Empowering our members through financial cooperation" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                    @error('cooperative_tagline') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2">
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
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="timezone" class="mb-2 block text-sm font-medium text-slate-200">Timezone</label>
                        <select id="timezone" name="timezone" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            @php($selectedTimezone = old('timezone', $settings['timezone'] ?? config('app.timezone', 'UTC')))
                            @foreach(\DateTimeZone::listIdentifiers() as $tz)
                                <option value="{{ $tz }}" @selected($tz === $selectedTimezone)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="currency_symbol" class="mb-2 block text-sm font-medium text-slate-200">Currency Symbol</label>
                        <input id="currency_symbol" name="currency_symbol" type="text" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '₱') }}" maxlength="5" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                        @error('currency_symbol') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="date_format" class="mb-2 block text-sm font-medium text-slate-200">Date Format</label>
                        <select id="date_format" name="date_format" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            @php($selectedFormat = old('date_format', $settings['date_format'] ?? 'M d, Y'))
                            @foreach(['M d, Y' => 'Jan 15, 2024', 'm/d/Y' => '01/15/2024', 'd/m/Y' => '15/01/2024', 'Y-m-d' => '2024-01-15', 'F j, Y' => 'January 15, 2024', 'j F Y' => '15 January 2024'] as $format => $example)
                                <option value="{{ $format }}" @selected($format === $selectedFormat)>{{ $example }}</option>
                            @endforeach
                        </select>
                        @error('date_format') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="items_per_page" class="mb-2 block text-sm font-medium text-slate-200">Items Per Page</label>
                        <select id="items_per_page" name="items_per_page" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            @foreach([10, 15, 25, 50, 100] as $count)
                                <option value="{{ $count }}" @selected((int) old('items_per_page', $settings['items_per_page'] ?? 15) === $count)>{{ $count }}</option>
                            @endforeach
                        </select>
                        @error('items_per_page') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">Save Settings</button>
            </div>
        </form>
    </div>

    {{-- APPEARANCE TAB --}}
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
                            <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" @change="handleLogoChange" class="mt-3 block w-full text-sm text-slate-400 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-white/[0.06] file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-200 hover:file:bg-white/[0.09]" />
                            @error('logo') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </label>

                    <div>
                        <p class="text-sm font-semibold text-white">Accent Color</p>
                        <p class="mt-1 text-sm text-slate-500">Choose a primary accent color for the sidebar, headers, and buttons.</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            @php($accentColors = ['green' => '#22c55e', 'blue' => '#3b82f6', 'indigo' => '#6366f1', 'purple' => '#a855f7', 'teal' => '#14b8a6'])
                            @foreach($accentColors as $key => $color)
                                <label class="relative flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-white/10 bg-[#0f1319] px-4 py-3 transition has-[:checked]:border-[var(--pm-accent)] has-[:checked]:bg-[rgba(var(--pm-accent-rgb),0.08)]">
                                    <input type="radio" name="accent_color" value="{{ $key }}" @checked(old('accent_color', $settings['accent_color'] ?? 'green') === $key) class="sr-only">
                                    <span class="h-6 w-6 rounded-full" style="background-color: {{ $color }}"></span>
                                    <span class="text-xs font-medium capitalize text-slate-300">{{ $key }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">Save Appearance</button>
                </div>
            </div>
        </form>
    </div>

    {{-- SECURITY TAB --}}
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

                <form method="POST" action="{{ route('settings.password', $tenantParameter, false) }}" class="mt-6 space-y-5" x-data="{ showCurrentPassword: false, showNewPassword: false, showConfirmPassword: false, password: '', confirmPassword: '' }">
                    @csrf

                    <div>
                        <label for="current_password" class="mb-2 block text-sm font-medium text-slate-200">Current Password</label>
                        <div class="relative">
                            <input id="current_password" name="current_password" x-bind:type="showCurrentPassword ? 'text' : 'password'" autocomplete="current-password" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 pr-12 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            <button type="button" x-on:click="showCurrentPassword = !showCurrentPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-slate-400 transition hover:text-slate-200" x-bind:aria-label="showCurrentPassword ? 'Hide current password' : 'Show current password'">
                                <svg x-show="!showCurrentPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="showCurrentPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        @error('current_password') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-200">New Password</label>
                        <div class="relative">
                            <input id="password" name="password" x-bind:type="showNewPassword ? 'text' : 'password'" x-model="password" autocomplete="new-password" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 pr-12 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            <button type="button" x-on:click="showNewPassword = !showNewPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-slate-400 transition hover:text-slate-200" x-bind:aria-label="showNewPassword ? 'Hide new password' : 'Show new password'">
                                <svg x-show="!showNewPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="showNewPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        @error('password') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-200">Confirm New Password</label>
                        <div class="relative">
                            <input id="password_confirmation" name="password_confirmation" x-bind:type="showConfirmPassword ? 'text' : 'password'" x-model="confirmPassword" autocomplete="new-password" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 pr-12 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                            <button type="button" x-on:click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-slate-400 transition hover:text-slate-200" x-bind:aria-label="showConfirmPassword ? 'Hide confirmation' : 'Show confirmation'">
                                <svg x-show="!showConfirmPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="showConfirmPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <p class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                            <template x-if="password.length >= 8">
                                <span class="text-emerald-400">Minimum 8 characters met.</span>
                            </template>
                            <template x-if="password.length > 0 && password.length < 8">
                                <span class="text-amber-400">Minimum 8 characters (@{{ 8 - password.length }} more).</span>
                            </template>
                            <template x-if="password.length === 0">
                                <span>Minimum 8 characters.</span>
                            </template>
                        </p>
                        @error('password_confirmation') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SUPPORT TAB --}}
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
                                <input id="subject" name="subject" type="text" value="{{ old('subject', $supportPrefillSubject) }}" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]" placeholder="Describe your concern" required>
                                @error('subject') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="min-w-0">
                                <label for="category" class="mb-2 block text-sm font-medium text-slate-200">Category</label>
                                <select id="category" name="category" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]">
                                    @foreach(['general' => 'General', 'technical' => 'Technical', 'billing' => 'Billing', 'account' => 'Account', 'feature' => 'Feature Request'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('category', $supportPrefillCategory) === $value)>{{ $label }}</option>
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
                                <textarea id="message" name="message" rows="6" class="block w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white placeholder-slate-500 transition focus:border-[var(--pm-accent)] focus:outline-none focus:ring-2 focus:ring-[rgba(var(--pm-accent-rgb),0.18)]" placeholder="Explain the issue, what happened, and what you need help with." required>{{ old('message', $supportPrefillMessage) }}</textarea>
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
                                                    <p class="text-xs text-slate-500">{{ formatDate($response->created_at, true) }}</p>
                                                </div>
                                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-200">{{ $response->message }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                                    <span>Submitted {{ formatDate($supportRequest->created_at, true) }}</span>
                                    @if($supportRequest->resolved_at)
                                        <span>Resolved {{ formatDate($supportRequest->resolved_at, true) }}</span>
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
