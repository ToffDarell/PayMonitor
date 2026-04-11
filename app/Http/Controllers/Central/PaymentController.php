<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Mail\TenantReceiptMail;
use App\Models\BillingInvoice;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(static function ($request, $next) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $tenants = Tenant::query()
            ->with('plan')
            ->orderBy('subscription_due_at')
            ->paginate(15);

        $tenants->setCollection($tenants->getCollection()->map(function (Tenant $tenant): Tenant {
            $tenant->setAttribute('payment_status', $this->resolvePaymentStatus($tenant->subscription_due_at));

            return $tenant;
        }));

        return view('central.payments.index', compact('tenants'));
    }

    public function markPaid(Tenant $tenant): RedirectResponse
    {
        $invoice = BillingInvoice::firstOrCreateForTenantCycle(
            $tenant->loadMissing('plan'),
            $tenant->subscription_due_at ?? today(),
            'Payment recorded from the payments overview.',
        );

        $invoice->markPaidAndRenewTenant();
        $invoice->loadMissing('tenant.plan');
        $nextDueDate = $invoice->tenant->subscription_due_at?->format('M d, Y') ?? 'Not set';

        Mail::to($invoice->tenant->email)->send(new TenantReceiptMail(
            $invoice->tenant,
            $invoice,
            $invoice->tenant->subscription_due_at,
        ));

        return redirect()
            ->to(route('central.billing.show', $invoice, false))
            ->with('success', "Payment recorded in billing and receipt sent. New due date: {$nextDueDate}.");
    }

    protected function resolvePaymentStatus(?CarbonInterface $dueDate): string
    {
        if ($dueDate === null || $dueDate->lt(today())) {
            return 'overdue';
        }

        if ($dueDate->lte(today()->copy()->addDays(7))) {
            return 'due_soon';
        }

        return 'current';
    }
}
