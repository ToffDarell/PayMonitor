<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\Central\AuthenticatedSessionController as CentralAuthenticatedSessionController;
use App\Http\Controllers\Central\BillingController as CentralBillingController;
use App\Http\Controllers\Central\DashboardController as CentralDashboardController;
use App\Http\Controllers\Central\PaymentController as CentralPaymentController;
use App\Http\Controllers\Central\PlanController as CentralPlanController;
use App\Http\Controllers\Central\TenantController as CentralTenantController;
use App\Http\Controllers\Central\ApplicationController as CentralApplicationController;
use App\Http\Controllers\Central\VersionController as CentralVersionController;
use App\Http\Controllers\Central\SupportController as CentralSupportController;
use App\Http\Controllers\ApplicationController;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains', ['localhost']) as $domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', static function () {
            $plans = Plan::query()
                ->orderBy('price')
                ->orderBy('name')
                ->get();

            return view('welcome', compact('plans'));
        })->name('welcome');

        Route::get('/apply', [ApplicationController::class, 'create'])->name('apply.create');
        Route::post('/apply', [ApplicationController::class, 'store'])->name('apply.store');
        Route::view('/apply/thank-you', 'apply-thankyou')->name('apply.thank-you');

        Route::middleware('guest')->group(function (): void {
            Route::get('/login', [CentralAuthenticatedSessionController::class, 'create'])->name('central.login');
            Route::post('/login', [CentralAuthenticatedSessionController::class, 'store'])->name('central.login.store');
            Route::get('/register', static fn (): RedirectResponse => redirect('/login')->with('error', 'Registration is closed.'))->name('central.register');
        });

        Route::match(['get', 'post'], '/logout', [CentralAuthenticatedSessionController::class, 'destroy'])
            ->middleware('auth')
            ->name('central.logout');

        Route::prefix('central')
            ->name('central.')
            ->middleware(['auth', 'role:super_admin'])
            ->group(function (): void {
                Route::get('/dashboard', [CentralDashboardController::class, 'index'])->name('dashboard');

                Route::get('/applications', [CentralApplicationController::class, 'index'])->name('applications.index');
                Route::get('/applications/{application}', [CentralApplicationController::class, 'show'])->name('applications.show');
                Route::get('/applications/{application}/payment-proof', [CentralApplicationController::class, 'paymentProof'])->name('applications.payment-proof');
                Route::post('/applications/{application}/verify-payment', [CentralApplicationController::class, 'verifyPayment'])->name('applications.verify-payment');
                Route::post('/applications/{application}/reject-payment', [CentralApplicationController::class, 'rejectPayment'])->name('applications.reject-payment');
                Route::post('/applications/{application}/approve', [CentralApplicationController::class, 'approve'])->name('applications.approve');
                Route::post('/applications/{application}/reject', [CentralApplicationController::class, 'reject'])->name('applications.reject');

                Route::post('/tenants/{tenant}/suspend', [CentralTenantController::class, 'suspend'])->name('tenants.suspend');
                Route::post('/tenants/{tenant}/activate', [CentralTenantController::class, 'activate'])->name('tenants.activate');
                Route::post('/tenants/{tenant}/resend-credentials', [CentralTenantController::class, 'resendCredentials'])->name('tenants.resend-credentials');
                Route::resource('tenants', CentralTenantController::class);

                Route::resource('plans', CentralPlanController::class)->except('show');

                Route::get('/payments', [CentralPaymentController::class, 'index'])->name('payments.index');
                Route::post('/payments/{tenant}/mark-paid', [CentralPaymentController::class, 'markPaid'])->name('payments.mark-paid');

                Route::get('/billing', [CentralBillingController::class, 'index'])->name('billing.index');
                Route::get('/billing/{invoice}', [CentralBillingController::class, 'show'])->name('billing.show');
                Route::post('/billing/{tenant}/send-invoice', [CentralBillingController::class, 'sendInvoice'])->name('billing.send-invoice');
                Route::post('/billing/{invoice}/mark-paid', [CentralBillingController::class, 'markPaid'])->name('billing.mark-paid');
                Route::post('/billing/{invoice}/send-receipt', [CentralBillingController::class, 'sendReceipt'])->name('billing.send-receipt');

                Route::get('/versions', [CentralVersionController::class, 'index'])->name('versions.index');
                Route::post('/versions/sync', [CentralVersionController::class, 'syncReleases'])->name('versions.sync');
                Route::post('/versions/backfill-tracking', [CentralVersionController::class, 'backfillTracking'])->name('versions.backfill-tracking');
                Route::post('/versions/{release}/mark-required', [CentralVersionController::class, 'markRequired'])->name('versions.mark-required');
                Route::delete('/versions/{release}/unmark-required', [CentralVersionController::class, 'unmarkRequired'])->name('versions.unmark-required');
                Route::post('/versions/{release}/notify-all', [CentralVersionController::class, 'notifyAll'])->name('versions.notify-all');
                Route::post('/versions/{release}/force-mark-all', [CentralVersionController::class, 'forceMarkAll'])->name('versions.force-mark-all');
                Route::post('/versions/check', [CentralVersionController::class, 'checkForUpdates'])->name('versions.check');
                Route::post('/versions/apply', [CentralVersionController::class, 'applyUpdate'])->name('versions.apply');

                Route::get('/support', [CentralSupportController::class, 'index'])->name('support.index');
                Route::get('/support/{supportRequest}', [CentralSupportController::class, 'show'])->name('support.show');
                Route::patch('/support/{supportRequest}/status', [CentralSupportController::class, 'updateStatus'])->name('support.update-status');
                Route::post('/support/{supportRequest}/response', [CentralSupportController::class, 'storeResponse'])->name('support.store-response');
            });
    });
}
