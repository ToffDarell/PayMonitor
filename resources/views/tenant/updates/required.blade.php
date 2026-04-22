@extends('layouts.tenant')

@section('title', 'Update Required')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h2 class="fw-bold mb-3">System Update Required</h2>
                    
                    <p class="text-muted mb-4">
                        Your system must be updated to version <strong>{{ $requiredUpdate->appRelease->version }}</strong> to continue.
                        @if($requiredUpdate->grace_until && now()->isAfter($requiredUpdate->grace_until))
                            The grace period has expired.
                        @endif
                    </p>

                    <div class="alert alert-warning text-start mb-4">
                        <strong>What happens during the update?</strong>
                        <ul class="mb-0 mt-2">
                            <li>Database migrations will be applied to your tenant</li>
                            <li>The update typically takes 2-5 minutes</li>
                            <li>Your data will remain intact</li>
                        </ul>
                    </div>

                    <a href="{{ route('tenant.updates.index', ['tenant' => $tenantId]) }}" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-arrow-right-circle me-2"></i>Go to Updates
                    </a>

                    <div class="mt-4">
                        <a href="{{ route('tenant.logout', ['tenant' => $tenantId], false) }}" class="text-muted small">
                            <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
