@extends('layouts.central')

@section('content')
    <div class="px-4 py-8 max-w-5xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-8">
            <nav class="sm:hidden mb-4" aria-label="Back">
                <a href="{{ route('central.applications.index', absolute: false) }}" class="flex items-center text-sm font-medium text-gray-400 hover:text-white">
                    <svg class="-ml-1 mr-1 h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                    Back to Applications
                </a>
            </nav>
            <nav class="hidden sm:flex mb-4" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <div class="flex">
                            <a href="{{ route('central.applications.index', absolute: false) }}" class="text-sm font-medium text-gray-400 hover:text-white">Applications</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-4 text-sm font-medium text-white" aria-current="page">Review Application</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-white sm:truncate sm:text-3xl sm:tracking-tight">
                        {{ $application->cooperative_name }}
                    </h2>
                    <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                        <div class="mt-2 flex items-center text-sm text-gray-400">
                            Requested Domain: <strong class="text-emerald-400 ml-1">{{ $application->domain }}.{{ config('tenancy.central_domains')[0] ?? 'localhost' }}</strong>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-400">
                            Submitted: {{ $application->created_at->format('F j, Y, g:i a') }}
                        </div>
                        <div class="mt-2 flex items-center">
                            @if($application->status === 'pending')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium bg-yellow-400/10 text-yellow-500 border border-yellow-400/20">
                                    Pending Review
                                </span>
                            @elseif($application->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    Approved
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                    Rejected
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                
                @if($application->status === 'pending')
                    <div class="mt-4 flex md:ml-4 md:mt-0 space-x-3">
                        <form action="{{ route('central.applications.reject', $application->id, false) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this application?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-300 shadow-sm ring-1 ring-inset ring-gray-600 hover:bg-gray-700 hover:text-white transition">
                                Reject
                            </button>
                        </form>
                        <form action="{{ route('central.applications.approve', $application->id, false) }}" method="POST" onsubmit="return confirm('Approving will create the tenant database and super-admin user. Proceed?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition">
                                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Approve & Create Tenant
                            </button>
                        </form>
                    </div>
                @endif
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

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Cooperative Info Card -->
                <div class="bg-[#111827] shadow-xl sm:rounded-xl border border-gray-800 overflow-hidden ring-1 ring-white/5">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-800/60 bg-gray-800/30">
                        <h3 class="text-base font-semibold leading-6 text-white">Cooperative Information</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-400">Details provided by the applicant about their organization.</p>
                    </div>
                    <div class="border-t border-gray-800">
                        <dl class="divide-y divide-gray-800/60">
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-800/20 transition">
                                <dt class="text-sm font-medium text-gray-400">Organization Name</dt>
                                <dd class="mt-1 text-sm text-white sm:col-span-2 sm:mt-0">{{ $application->cooperative_name }}</dd>
                            </div>
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-800/20 transition">
                                <dt class="text-sm font-medium text-gray-400">Company Size</dt>
                                <dd class="mt-1 text-sm text-white sm:col-span-2 sm:mt-0">{{ $application->company_size ?? 'Not specified' }}</dd>
                            </div>
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-800/20 transition">
                                <dt class="text-sm font-medium text-gray-400">CDA Registration Number</dt>
                                <dd class="mt-1 text-sm text-white sm:col-span-2 sm:mt-0">{{ $application->cda_registration_number ?: 'Not provided' }}</dd>
                            </div>
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-800/20 transition">
                                <dt class="text-sm font-medium text-gray-400">Expected Users</dt>
                                <dd class="mt-1 text-sm text-white sm:col-span-2 sm:mt-0">{{ $application->expected_users ?? 'Unknown' }}</dd>
                            </div>
                            @if($application->message)
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-800/20 transition">
                                <dt class="text-sm font-medium text-gray-400">Additional Message</dt>
                                <dd class="mt-1 text-sm text-gray-300 sm:col-span-2 sm:mt-0 bg-gray-900/50 p-3 rounded border border-gray-800">
                                    {{ $application->message }}
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Admin Contact Card -->
                <div class="bg-[#111827] shadow-xl sm:rounded-xl border border-gray-800 overflow-hidden ring-1 ring-white/5">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-800/60 bg-gray-800/30">
                        <h3 class="text-base font-semibold leading-6 text-white">Administrator Contact</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-400">The person who will become the Super Admin.</p>
                    </div>
                    <div class="border-t border-gray-800">
                        <dl class="divide-y divide-gray-800/60">
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-800/20 transition">
                                <dt class="text-sm font-medium text-gray-400">Full Name</dt>
                                <dd class="mt-1 text-sm text-white sm:col-span-2 sm:mt-0 flex items-center">
                                    <svg class="mr-1.5 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ $application->admin_name }}
                                </dd>
                            </div>
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-800/20 transition">
                                <dt class="text-sm font-medium text-gray-400">Email Address</dt>
                                <dd class="mt-1 text-sm text-white sm:col-span-2 sm:mt-0 flex items-center">
                                    <svg class="mr-1.5 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    <a href="mailto:{{ $application->admin_email }}" class="text-emerald-400 hover:text-emerald-300">{{ $application->admin_email }}</a>
                                </dd>
                            </div>
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-800/20 transition">
                                <dt class="text-sm font-medium text-gray-400">Phone Number</dt>
                                <dd class="mt-1 text-sm text-white sm:col-span-2 sm:mt-0 flex items-center">
                                    <svg class="mr-1.5 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    {{ $application->contact_number ?? 'Not provided' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sidebar -->
            <div class="space-y-6">
                <!-- Plan Card -->
                <div class="bg-[#111827] shadow-xl sm:rounded-xl border border-gray-800 overflow-hidden ring-1 ring-white/5">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-800/60 bg-gray-800/30">
                        <h3 class="text-base font-semibold leading-6 text-white">Selected Plan</h3>
                    </div>
                    <div class="p-6">
                        @if($application->plan)
                            <div class="text-center">
                                <h4 class="text-lg font-bold text-indigo-400">{{ $application->plan->name }}</h4>
                                <div class="mt-2 flex items-baseline justify-center text-3xl font-extrabold text-white">
                                    ${{ number_format($application->plan->price, 2) }}
                                    <span class="ml-1 text-sm font-medium text-gray-500">/mo</span>
                                </div>
                                <p class="mt-4 text-sm text-gray-400">{{ $application->plan->description }}</p>
                            </div>
                            
                            <ul class="mt-6 space-y-3 text-sm text-gray-300">
                                @foreach($application->plan->features ?? [] as $feature)
                                    <li class="flex gap-x-2">
                                        <svg class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-400">No plan selected</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Status Metadata Card -->
                <div class="bg-[#111827] shadow-xl sm:rounded-xl border border-gray-800 overflow-hidden ring-1 ring-white/5">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-800/60 bg-gray-800/30">
                        <h3 class="text-base font-semibold leading-6 text-white">Review Status</h3>
                    </div>
                    <div class="p-4">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500">Reviewed By</p>
                                <p class="text-sm font-medium text-white">{{ $application->reviewer ? $application->reviewer->name : 'Unassigned' }}</p>
                            </div>
                            @if($application->reviewed_at)
                            <div>
                                <p class="text-xs text-gray-500">Review Date</p>
                                <p class="text-sm font-medium text-white">{{ $application->reviewed_at->format('M j, Y g:i A') }}</p>
                            </div>
                            @endif
                            @if($application->tenant_id)
                            <div>
                                <p class="text-xs text-gray-500">Linked Tenant ID</p>
                                <p class="text-sm font-medium text-emerald-400">{{ $application->tenant_id }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
