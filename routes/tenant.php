<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\Tenant\AuthenticatedSessionController as TenantAuthenticatedSessionController;
use App\Http\Controllers\Auth\Tenant\NewPasswordController as TenantNewPasswordController;
use App\Http\Controllers\Auth\Tenant\PasswordResetLinkController as TenantPasswordResetLinkController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\AuditLogController;
use App\Http\Controllers\Tenant\CollectionController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\LoanController;
use App\Http\Controllers\Tenant\LoanDocumentController;
use App\Http\Controllers\Tenant\LoanPaymentController;
use App\Http\Controllers\Tenant\LoanTypeController;
use App\Http\Controllers\Tenant\MemberController;
use App\Http\Controllers\Tenant\MemberDocumentController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\UserController;
use App\Support\TenantPermissions;
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
                'tenant.active',
            ])
            ->group(function (): void {
                Route::get('/', static fn (): RedirectResponse => redirect('/login'));

                Route::middleware('guest')->group(function (): void {
                    Route::get('/login', [TenantAuthenticatedSessionController::class, 'create'])->name('tenant.login');
                    Route::post('/login', [TenantAuthenticatedSessionController::class, 'store'])->name('tenant.login.store');
                    Route::get('/forgot-password', [TenantPasswordResetLinkController::class, 'create'])->name('tenant.password.request');
                    Route::post('/forgot-password', [TenantPasswordResetLinkController::class, 'store'])->name('tenant.password.email');
                    Route::get('/reset-password/{token}', [TenantNewPasswordController::class, 'create'])->name('tenant.password.reset');
                    Route::post('/reset-password', [TenantNewPasswordController::class, 'store'])->name('tenant.password.store');
                    Route::get('/register', static fn (): RedirectResponse => redirect('/login')->with('error', 'Registration is closed.'))->name('tenant.register');
                });

                Route::match(['get', 'post'], '/logout', [TenantAuthenticatedSessionController::class, 'destroy'])
                    ->middleware('auth')
                    ->name('tenant.logout');

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required'])->group(function (): void {
                    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
                    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
                    Route::match(['get', 'post'], '/billing/{invoiceId}/pay', [BillingController::class, 'initiatePayment'])->name('billing.pay');
                    Route::get('/billing/{invoiceId}/success', [BillingController::class, 'paymentSuccess'])->name('billing.success');
                    Route::get('/billing/{invoiceId}/failed', [BillingController::class, 'paymentFailed'])->name('billing.failed');
                    Route::post('/billing/{invoiceId}/verify', [BillingController::class, 'verifyPayment'])->name('billing.verify');

                    Route::resource('members', MemberController::class);
                    Route::post('/loans/compute-preview', [LoanController::class, 'computePreview'])->name('loans.compute-preview');
                    Route::resource('loans', LoanController::class);
                    Route::resource('loan-payments', LoanPaymentController::class)->only(['index', 'create', 'store']);
                    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
                    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
                    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
                    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::MEMBER_DOCUMENTS_UPLOAD])->group(function (): void {
                    Route::post('/members/{member}/documents', [MemberDocumentController::class, 'store'])->name('member.documents.store');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::MEMBER_DOCUMENTS_DELETE])->group(function (): void {
                    Route::delete('/members/documents/{document}', [MemberDocumentController::class, 'destroy'])->name('member.documents.destroy');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::MEMBER_DOCUMENTS_VIEW])->group(function (): void {
                    Route::get('/members/documents/{document}/download', [MemberDocumentController::class, 'download'])->name('member.documents.download');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::LOAN_DOCUMENTS_UPLOAD])->group(function (): void {
                    Route::post('/loans/{loan}/documents', [LoanDocumentController::class, 'store'])->name('loan.documents.store');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::LOAN_DOCUMENTS_DELETE])->group(function (): void {
                    Route::delete('/loans/documents/{document}', [LoanDocumentController::class, 'destroy'])->name('loan.documents.destroy');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::LOAN_DOCUMENTS_VIEW])->group(function (): void {
                    Route::get('/loans/documents/{document}/download', [LoanDocumentController::class, 'download'])->name('loan.documents.download');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::COLLECTIONS_VIEW])->group(function (): void {
                    Route::get('/collections', [CollectionController::class, 'index'])->name('tenant.collections');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::AUDIT_LOGS_VIEW])->group(function (): void {
                    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('tenant.audit-logs');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::LOAN_TYPES_VIEW])->group(function (): void {
                    Route::resource('loan-types', LoanTypeController::class);
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::BRANCHES_VIEW])->group(function (): void {
                    Route::resource('branches', BranchController::class);
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::USERS_VIEW])->group(function (): void {
                    Route::get('/users/roles', [RoleController::class, 'index'])->name('users.roles.index');
                    Route::get('/users/roles/create', [RoleController::class, 'create'])->name('users.roles.create');
                    Route::post('/users/roles', [RoleController::class, 'store'])->name('users.roles.store');
                    Route::get('/users/roles/{role}/edit', [RoleController::class, 'edit'])->name('users.roles.edit');
                    Route::put('/users/roles/{role}', [RoleController::class, 'update'])->name('users.roles.update');
                    Route::delete('/users/roles/{role}', [RoleController::class, 'destroy'])->name('users.roles.destroy');

                    Route::resource('users', UserController::class);
                    Route::post('/users/{user}/resend-credentials', [UserController::class, 'resendCredentials'])->name('users.resend-credentials');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::SETTINGS_VIEW])->group(function (): void {
                    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required'])->group(function (): void {
                    Route::get('/settings/updates', [SettingsController::class, 'updates'])->name('settings.updates');
                    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
                });

                Route::middleware(['auth', 'tenant.context', 'tenant.update.required', 'tenant.permission:'.TenantPermissions::SETTINGS_UPDATE])->group(function (): void {
                    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
                    Route::post('/settings/support', [SettingsController::class, 'submitSupport'])->name('settings.support');
                    Route::post('/settings/updates/apply', [SettingsController::class, 'applyUpdate'])->name('settings.updates.apply');
                });

                Route::fallback(static function (): void {
                    abort(404);
                });
            });
    });
