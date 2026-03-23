@extends('layouts.tenant')

@section('title', 'User Credentials')

@section('content')
<div class="card border-0 shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <h5 class="fw-bold mb-3">User created successfully</h5>
        <p class="text-muted">Copy these credentials now. The temporary password is shown only once.</p>
        <dl class="row mb-4">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $user->name }}</dd>
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9">{{ $user->email }}</dd>
            <dt class="col-sm-3">Password</dt>
            <dd class="col-sm-9"><code class="fs-6">{{ $password }}</code></dd>
        </dl>
        <a href="{{ url('/users/'.$user->id) }}" class="btn btn-primary">Continue</a>
    </div>
</div>
@endsection

