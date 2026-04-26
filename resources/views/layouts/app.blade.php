<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PayMonitor') }} — @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/paymonitor.css'])
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<div class="sidebar">
    <div class="brand">Pay<span>Monitor</span></div>
    <nav class="mt-2">
        @php
            $role = auth()->user()->getRoleNames()->first() ?? 'user';
            $isCentralUser = auth()->user()->hasRole('super_admin');
            $logoutUrl = $isCentralUser
                ? route('central.logout', absolute: false)
                : route('tenant.logout', ['tenant' => tenant('id') ?? request()->route('tenant') ?? current(explode('.', request()->getHost()))], false);
        @endphp

        @if($isCentralUser)
            <div class="nav-section">Central App</div>
            <a href="{{ url('/central/dashboard') }}" class="nav-link {{ request()->is('central/dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ url('/central/tenants') }}" class="nav-link {{ request()->is('central/tenants*') ? 'active' : '' }}">
                <i class="bi bi-buildings"></i> Tenants
            </a>
            <a href="{{ url('/central/plans') }}" class="nav-link {{ request()->is('central/plans*') ? 'active' : '' }}">
                <i class="bi bi-card-list"></i> Plans
            </a>
            <a href="{{ url('/central/payments') }}" class="nav-link {{ request()->is('central/payments*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> Payments
            </a>
        @else
            <div class="nav-section">Main</div>
            <a href="{{ url('/dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            @if(in_array($role, ['tenant_admin', 'branch_manager'], true))
                <div class="nav-section">Operations</div>
                <a href="{{ url('/branches') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> Branches
                </a>
                <a href="{{ url('/users') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i> Users
                </a>
            @endif

            <div class="nav-section">Lending</div>
            <a href="{{ url('/members') }}" class="nav-link {{ request()->routeIs('members.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Members
            </a>
            <a href="{{ url('/loan-types') }}" class="nav-link {{ request()->routeIs('loan-types.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Loan Types
            </a>
            <a href="{{ url('/loans') }}" class="nav-link {{ request()->routeIs('loans.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Loans
            </a>
            <a href="{{ url('/loan-payments') }}" class="nav-link {{ request()->routeIs('loan-payments.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Payments
            </a>
            <a href="{{ url('/reports') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart"></i> Reports
            </a>

            <div class="nav-section">Account</div>
            <a href="{{ url('/profile') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> Profile
            </a>
        @endif
    </nav>
</div>

{{-- Main Content --}}
<div class="main-content">
    <div class="topbar">
        <div class="fw-semibold text-muted">@yield('title', 'Dashboard')</div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">{{ auth()->user()->name }}</span>
            <span class="badge bg-secondary text-uppercase">{{ str_replace('_', ' ', $role) }}</span>
            <form method="POST" action="{{ $logoutUrl }}"
                data-confirm="You will be signed out of the current portal."
                data-confirm-title="Log out now?"
                data-confirm-confirm-text="Log out"
                data-confirm-tone="danger"
                class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

@include('partials.dialogs')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
