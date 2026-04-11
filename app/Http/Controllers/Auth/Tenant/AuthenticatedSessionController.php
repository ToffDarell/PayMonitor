<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        $request->session()->regenerateToken();
        $tenant = tenant();
        $tenantHost = $tenant?->domains()->value('domain') ?? request()->getHost();
        $tenantName = $tenant?->name ?? 'Cooperative';

        return response()
            ->view('auth.tenant-login', compact('tenantHost', 'tenantName'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $tenant = tenant();

        if ($tenant instanceof Tenant && in_array($tenant->status, ['suspended', 'inactive'], true)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'This account has been suspended. Contact support.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if ($user instanceof User && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'This user account is inactive. Contact your tenant administrator.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $landingPath = $user instanceof User
            ? $user->preferredTenantLandingPath()
            : '/dashboard';

        return redirect()->intended($landingPath);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
