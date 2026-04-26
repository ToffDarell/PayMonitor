@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $usersActive = request()->routeIs('users.index', 'users.create', 'users.store', 'users.show', 'users.edit', 'users.update');
    $rolesActive = request()->routeIs('users.roles.*');
    $customRolesEnabled = \App\Support\TenantFeatures::tenantHasFeature('custom_roles');
@endphp

<div class="mb-4">
    <div class="nav nav-pills gap-2">
        <a href="{{ route('users.index', $tenantParameter) }}" class="btn {{ $usersActive ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-people-fill me-2"></i>Users
        </a>
        @if($customRolesEnabled)
            <a href="{{ route('users.roles.index', $tenantParameter) }}" class="btn {{ $rolesActive ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="bi bi-shield-lock-fill me-2"></i>Roles
            </a>
        @endif
    </div>
</div>
