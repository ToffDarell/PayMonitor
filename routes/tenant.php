<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\Tenant\AuthenticatedSessionController as TenantAuthenticatedSessionController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\LoanController;
use App\Http\Controllers\Tenant\LoanPaymentController;
use App\Http\Controllers\Tenant\LoanTypeController;
use App\Http\Controllers\Tenant\MemberController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\UserController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

collect(config('tenancy.central_domains', ['localhost']))
    ->filter(static fn (string $domain): bool => ! in_array($domain, ['127.0.0.1'], true))
    ->each(function (string $domain): void {
        Route::domain("{tenant}.{$domain}")
            ->middleware([
                'web',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->group(function (): void {
                Route::get('/', static fn (): RedirectResponse => redirect('/login'));

                Route::middleware('guest')->group(function (): void {
                    Route::get('/login', [TenantAuthenticatedSessionController::class, 'create'])->name('tenant.login');
                    Route::post('/login', [TenantAuthenticatedSessionController::class, 'store'])->name('tenant.login.store');
                    Route::get('/register', static fn (): RedirectResponse => redirect('/login')->with('error', 'Registration is closed.'))->name('tenant.register');
                });

                Route::match(['get', 'post'], '/logout', [TenantAuthenticatedSessionController::class, 'destroy'])
                    ->middleware('auth')
                    ->name('tenant.logout');

                Route::middleware(['auth', 'tenant.context', 'tenant.active'])->group(function (): void {
                    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

                    Route::resource('members', MemberController::class);
                    Route::resource('loan-types', LoanTypeController::class);
                    Route::post('/loans/compute-preview', [LoanController::class, 'computePreview'])->name('loans.compute-preview');
                    Route::resource('loans', LoanController::class);
                    Route::resource('loan-payments', LoanPaymentController::class)->only(['index', 'create', 'store']);
                    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
                    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
                    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
                    Route::get('/settings/updates', [SettingsController::class, 'updates'])->name('settings.updates');
                    Route::post('/settings/updates/{version}/acknowledge', [SettingsController::class, 'acknowledge'])->name('settings.acknowledge');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.active', 'role:tenant_admin'])->group(function (): void {
                    Route::resource('branches', BranchController::class);
                    Route::resource('users', UserController::class);
                });
            });
    });
