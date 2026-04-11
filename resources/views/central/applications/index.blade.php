@extends('layouts.central')

@section('content')
    <div class="px-4 py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-8 pb-4 border-b border-gray-700/50">
            <div>
                <h1 class="text-2xl font-semibold text-white tracking-tight">Applications</h1>
                <p class="mt-2 text-sm text-gray-400 max-w-2xl">
                    Review and manage incoming tenant cooperative applications. Approve applications to automatically create their tenant instance and super-admin user.
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex">
                <a href="{{ route('central.applications.index', ['status' => 'pending'], false) }}"
                   class="inline-flex items-center rounded-l-md border border-gray-600 px-4 py-2 text-sm font-medium {{ request('status') === 'pending' || !request('status') ? 'bg-gray-700 text-white' : 'bg-[#0f172a] text-gray-300 hover:bg-gray-800' }}">
                    Pending
                </a>
                <a href="{{ route('central.applications.index', ['status' => 'approved'], false) }}"
                   class="inline-flex items-center border-t border-b border-gray-600 px-4 py-2 text-sm font-medium {{ request('status') === 'approved' ? 'bg-gray-700 text-white' : 'bg-[#0f172a] text-gray-300 hover:bg-gray-800' }}">
                    Approved
                </a>
                <a href="{{ route('central.applications.index', ['status' => 'rejected'], false) }}"
                   class="inline-flex items-center rounded-r-md border border-l-0 border-gray-600 px-4 py-2 text-sm font-medium {{ request('status') === 'rejected' ? 'bg-gray-700 text-white' : 'bg-[#0f172a] text-gray-300 hover:bg-gray-800' }}">
                    Rejected
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-lg flex items-center mb-6 shadow-sm">
                <svg class="h-5 w-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-lg flex items-center mb-6 shadow-sm">
                <svg class="h-5 w-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-[#111827] shadow-xl sm:rounded-xl border border-gray-800 overflow-hidden ring-1 ring-white/5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-800">
                    <thead class="bg-gray-800/50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider sm:pl-6">Cooperative</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wider hidden sm:table-cell">Contact</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Plan</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Submitted</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Payment</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800 bg-transparent">
                        @forelse ($applications as $app)
                            <tr class="hover:bg-gray-800/30 transition-colors">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0 rounded bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20">
                                            <span class="text-emerald-400 font-semibold">{{ substr($app->cooperative_name, 0, 1) }}</span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="font-medium text-white">{{ $app->cooperative_name }}</div>
                                            <div class="text-xs text-gray-500 hidden sm:block mt-0.5">{{ $app->domain }}.{{ config('tenancy.tenant_base_domain', 'localhost') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300 hidden sm:table-cell">
                                    <div class="font-medium">{{ $app->admin_name }}</div>
                                    <div class="text-gray-500">{{ $app->admin_email }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">
                                    @if($app->plan)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                            {{ $app->plan->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-500">None</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $app->created_at->format('M j, Y') }}
                                    <div class="text-xs">{{ $app->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if($app->payment_status === 'verified')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Verified
                                        </span>
                                    @elseif($app->payment_status === 'rejected')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-yellow-400/10 text-yellow-500 border border-yellow-400/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if($app->status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-yellow-400/10 text-yellow-500 border border-yellow-400/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                                            Pending
                                        </span>
                                    @elseif($app->status === 'approved')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Approved
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <a href="{{ route('central.applications.show', $app->id, false) }}" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-gray-700 ring-1 ring-inset ring-gray-600 transition-colors">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="text-sm font-medium text-white mb-1">No applications found</h3>
                                    <p class="text-sm text-gray-400">There are no {{ request('status', 'pending') }} applications to review.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($applications->hasPages())
                <div class="border-t border-gray-800 px-4 py-3 sm:px-6 bg-gray-900/50">
                    {{ $applications->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
