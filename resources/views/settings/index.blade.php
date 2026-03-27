@extends('layouts.tenant')

@section('title', 'Settings')

@php
    $tenantParameter = ['tenant' => tenant()?->id ?? request()->route('tenant')];
    $logoPath = $settings['logo_path'] ?? null;
    $logoUrl = filled($logoPath)
        ? route('stancl.tenancy.asset', ['path' => ltrim((string) $logoPath, '/')], false)
        : null;
    $currentVersionLabel = $currentVersion?->version_number ? 'v'.$currentVersion->version_number : 'Not acknowledged yet';
    $latestVersionLabel = $latestVersion?->version_number ? 'v'.$latestVersion->version_number : 'No active release';
@endphp

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .settings-tab-active {
        border-color: rgba(var(--pm-accent-rgb), 0.35);
        background-color: rgba(var(--pm-accent-rgb), 0.12);
        color: #fff;
    }
</style>
@endpush

@section('content')
<div
    x-data="{
        activeTab: @js($activeTab),
        logoPreview: @js($logoUrl),
        selectedAccent: @js($settings['accent_color'] ?? 'green'),
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
    class="space-y-6"
>
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Tenant Settings</h2>
            <p class="mt-1 text-sm text-slate-400">Customize your portal details, appearance, and tenant update acknowledgements.</p>
        </div>
        <div class="flex flex-wrap gap-2 rounded-2xl border border-white/[0.07] bg-white/[0.02] p-2">
            <button type="button" x-on:click="activeTab = 'general'" x-bind:class="activeTab === 'general' ? 'settings-tab-active' : 'text-slate-400 hover:bg-white/[0.04] hover:text-white'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">General</button>
            <button type="button" x-on:click="activeTab = 'appearance'" x-bind:class="activeTab === 'appearance' ? 'settings-tab-active' : 'text-slate-400 hover:bg-white/[0.04] hover:text-white'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Appearance</button>
            <button type="button" x-on:click="activeTab = 'updates'" x-bind:class="activeTab === 'updates' ? 'settings-tab-active' : 'text-slate-400 hover:bg-white/[0.04] hover:text-white'" class="rounded-xl border border-transparent px-4 py-2 text-sm font-medium transition">Updates</button>
        </div>
    </div>

    <div x-cloak x-show="activeTab === 'general'" class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
        <form method="POST" action="{{ route('settings.update', $tenantParameter, false) }}" class="space-y-6">
            @csrf
            <input type="hidden" name="active_tab" value="general">
            <input type="hidden" name="accent_color" value="{{ old('accent_color', $settings['accent_color'] ?? 'green') }}">
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
                <p class="mt-2 text-sm text-slate-500">Most recent release acknowledged by this tenant.</p>
            </div>
            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Latest Available</p>
                <p class="mt-3 font-heading text-3xl font-bold text-white">{{ $latestVersionLabel }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $latestVersion?->title ?? 'No active version has been published in the central app.' }}</p>
            </div>
            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status</p>
                <p class="mt-3 font-heading text-3xl font-bold {{ $latestVersionAcknowledged ? 'text-emerald-300' : 'text-indigo-300' }}">{{ $latestVersionAcknowledged ? 'Up to Date' : 'Action Needed' }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $latestVersionAcknowledged ? 'The latest release has already been acknowledged.' : 'Review the changelog below and mark the update as acknowledged.' }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                <div class="flex flex-col gap-4 border-b border-white/[0.06] pb-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Latest Changelog</p>
                        <h3 class="mt-2 font-heading text-2xl font-bold text-white">{{ $latestVersion ? 'v'.$latestVersion->version_number.' - '.$latestVersion->title : 'No active update yet' }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $latestVersion?->released_at?->format('M d, Y') ?? 'Publish an active central version to start tenant update tracking.' }}</p>
                    </div>
                    @if($latestVersion && ! $latestVersionAcknowledged)
                        <form method="POST" action="{{ route('settings.acknowledge', [...$tenantParameter, 'version' => $latestVersion], false) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-500/20 px-4 py-2.5 text-sm font-medium text-indigo-100 transition hover:bg-indigo-500/30">Mark as Updated</button>
                        </form>
                    @endif
                </div>

                @if($latestVersion)
                    <ul class="mt-5 space-y-3 text-sm text-slate-300">
                        @foreach($latestVersion->changelog_items as $change)
                            <li class="flex gap-3">
                                <span class="mt-1.5 h-2 w-2 rounded-full bg-indigo-300"></span>
                                <span>{{ $change }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="mt-5 rounded-2xl border border-dashed border-white/[0.08] bg-[#0f1319] px-5 py-10 text-center">
                        <p class="text-sm text-slate-500">No active release has been published yet.</p>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
                <div class="border-b border-white/[0.06] pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Update History</p>
                    <h3 class="mt-2 font-heading text-xl font-bold text-white">All Releases</h3>
                    <p class="mt-2 text-sm text-slate-500">Track which versions have already been acknowledged by this tenant.</p>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse($versions as $version)
                        @php($ack = $acknowledgements->get($version->id))
                        <div class="rounded-2xl border border-white/[0.07] bg-[#0f1319] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-white">v{{ $version->version_number }}</p>
                                    <p class="mt-1 text-sm text-slate-400">{{ $version->title }}</p>
                                </div>
                                @if($ack)
                                    <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-300">Acknowledged</span>
                                @elseif($version->is_active)
                                    <span class="rounded-full border border-indigo-500/30 bg-indigo-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-indigo-300">Pending</span>
                                @else
                                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Archived</span>
                                @endif
                            </div>
                            <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                                <span>Released {{ $version->released_at?->format('M d, Y') ?? 'Not scheduled' }}</span>
                                @if($ack)
                                    <span>Acknowledged {{ $ack->acknowledged_at?->format('M d, Y h:i A') }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/[0.08] bg-[#0f1319] px-5 py-10 text-center">
                            <p class="text-sm text-slate-500">No version history available yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
