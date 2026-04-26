<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\TenantReceiptMail;
use App\Models\BillingInvoice;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class BillingController extends Controller
{
    public function __construct(
        protected PayMongoService $payMongoService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->boolean('verify') && $request->filled('invoice_id')) {
            $tenantId = $this->resolveTenantId($request);

            if ($tenantId === null) {
                return $this->billingRedirect('error', 'Tenant context could not be resolved.');
            }

            return $this->verifyPayment(
                $request,
                $tenantId,
                $request->string('invoice_id')->toString(),
            );
        }

        $tenant = tenant();

        abort_if($tenant === null, 404, 'Tenant context could not be resolved.');

        $invoices = BillingInvoice::query()
            ->where('tenant_id', (string) $tenant->id)
            ->latest('due_date')
            ->latest('id')
            ->get();

        return view('billing.index', compact('invoices'));
    }

    public function initiatePayment(Request $request, string $tenant, string $invoiceId): RedirectResponse
    {
        $invoice = $this->findInvoiceForCurrentTenant($invoiceId, $request);

        if (! $invoice instanceof BillingInvoice) {
            return $this->billingRedirect('error', 'The selected invoice could not be found for this tenant.');
        }

        if (! in_array($invoice->status, ['unpaid', 'overdue'], true)) {
            return $this->billingRedirect('error', 'This invoice is not available for payment.');
        }

        try {
            $paymentData = $this->payMongoService->createPaymentLink($invoice->loadMissing('tenant.plan'));

            $invoice->forceFill([
                'paymongo_link_id' => $paymentData['link_id'],
                'payment_url' => $paymentData['checkout_url'],
                'status' => 'pending_verification',
            ])->save();

            return redirect()->away($paymentData['checkout_url']);
        } catch (Throwable $exception) {
            report($exception);

            return $this->billingRedirect('error', 'Unable to connect to PayMongo right now. Please try again.');
        }
    }

    public function paymentSuccess(Request $request, string $tenant, string $invoiceId): RedirectResponse
    {
        $invoice = $this->findInvoiceForCurrentTenant($invoiceId, $request);

        if (! $invoice instanceof BillingInvoice) {
            return $this->billingRedirect('error', 'The selected invoice could not be found for this tenant.');
        }

        return $this->completePaymentVerification($invoice);
    }

    public function paymentFailed(Request $request, string $tenant, string $invoiceId): RedirectResponse
    {
        $invoice = $this->findInvoiceForCurrentTenant($invoiceId, $request);

        if (! $invoice instanceof BillingInvoice) {
            return $this->billingRedirect('error', 'The selected invoice could not be found for this tenant.');
        }

        return $this->billingRedirect('error', 'Payment was cancelled or failed. Please try again.');
    }

    public function verifyPayment(Request $request, string $tenant, string $invoiceId): RedirectResponse
    {
        $invoice = $this->findInvoiceForCurrentTenant($invoiceId, $request);

        if (! $invoice instanceof BillingInvoice) {
            return $this->billingRedirect('error', 'The selected invoice could not be found for this tenant.');
        }

        return $this->completePaymentVerification($invoice);
    }

    public function showReceipt(Request $request, string $tenant, string $invoiceId): View|RedirectResponse
    {
        $invoice = $this->findInvoiceForCurrentTenant($invoiceId, $request);

        if (! $invoice instanceof BillingInvoice || $invoice->status !== 'paid') {
            return $this->billingRedirect('error', 'Receipt not available.');
        }

        $tenantModel = $invoice->tenant ?? tenant();
        $nextBillingDate = $tenantModel?->subscription_due_at;

        return view('emails.tenant-receipt', [
            'tenant' => $tenantModel,
            'invoice' => $invoice,
            'nextBillingDate' => $nextBillingDate,
        ]);
    }

    protected function findInvoiceForCurrentTenant(string $invoiceId, ?Request $request = null): ?BillingInvoice
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId === null) {
            Log::warning('Unable to resolve tenant context for billing invoice lookup.', [
                'invoice_id' => $invoiceId,
                'host' => $request?->getHost() ?? request()->getHost(),
                'route_tenant' => $request?->route('tenant') ?? request()->route('tenant'),
            ]);

            return null;
        }

        $invoice = BillingInvoice::query()
            ->whereKey($invoiceId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $invoice instanceof BillingInvoice) {
            Log::warning('Billing invoice was not found for the resolved tenant.', [
                'invoice_id' => $invoiceId,
                'resolved_tenant_id' => $tenantId,
                'host' => $request?->getHost() ?? request()->getHost(),
            ]);
        }

        return $invoice;
    }

    protected function resolveTenantId(?Request $request = null): ?string
    {
        $tenant = tenant();

        if ($tenant instanceof Tenant) {
            return (string) $tenant->id;
        }

        if (app()->bound('current_tenant_id')) {
            $tenantId = app('current_tenant_id');

            if (filled($tenantId)) {
                return (string) $tenantId;
            }
        }

        $routeTenant = $request?->route('tenant') ?? request()->route('tenant');

        if ($routeTenant instanceof Tenant) {
            return (string) $routeTenant->id;
        }

        if (filled($routeTenant)) {
            return (string) $routeTenant;
        }

        $host = $request?->getHost() ?? request()->getHost();

        if (blank($host)) {
            return null;
        }

        $tenantId = Domain::query()
            ->where('domain', $host)
            ->value('tenant_id');

        return filled($tenantId) ? (string) $tenantId : null;
    }

    protected function completePaymentVerification(BillingInvoice $invoice): RedirectResponse
    {
        if (blank($invoice->paymongo_link_id)) {
            return $this->billingRedirect('error', 'No PayMongo payment link was found for this invoice.');
        }

        try {
            $verificationData = $this->payMongoService->verifyPayment((string) $invoice->paymongo_link_id);
        } catch (Throwable $exception) {
            report($exception);

            return $this->billingRedirect('error', 'Unable to verify the payment right now. Please try again.');
        }

        if (! ($verificationData['is_paid'] ?? false)) {
            return $this->billingRedirect('error', 'Payment not completed. Please try again.');
        }

        $connection = config('tenancy.database.central_connection', config('database.default'));
        $receiptTenant = null;
        $receiptInvoice = null;
        $nextBillingDate = null;
        $shouldSendReceipt = false;

        DB::connection($connection)->transaction(function () use (
            $invoice,
            $verificationData,
            &$receiptTenant,
            &$receiptInvoice,
            &$nextBillingDate,
            &$shouldSendReceipt,
        ): void {
            $lockedInvoice = BillingInvoice::query()
                ->lockForUpdate()
                ->with('tenant.plan')
                ->findOrFail($invoice->id);

            $lockedTenant = Tenant::query()
                ->lockForUpdate()
                ->with('plan')
                ->findOrFail($lockedInvoice->tenant_id);

            $shouldSendReceipt = $lockedInvoice->status !== 'paid';

            $lockedInvoice->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'paymongo_payment_id' => $verificationData['payment_id'],
                'payment_method' => $verificationData['method'],
                'paid_via' => 'paymongo',
            ])->save();

            if ($shouldSendReceipt) {
                $lockedTenant->forceFill([
                    'subscription_due_at' => now()->addDays(30),
                    'status' => 'active',
                ])->save();
            }

            $lockedInvoice->setRelation('tenant', $lockedTenant);
            $receiptTenant = $lockedTenant;
            $receiptInvoice = $lockedInvoice;
            $nextBillingDate = $lockedTenant->subscription_due_at;
        });

        if ($shouldSendReceipt && $receiptTenant instanceof Tenant && $receiptInvoice instanceof BillingInvoice) {
            Mail::to($receiptTenant->email)->send(new TenantReceiptMail(
                $receiptTenant,
                $receiptInvoice,
                $nextBillingDate,
            ));
        }

        return $this->billingRedirect('success', 'Payment successful! Your subscription has been renewed.');
    }

    protected function billingRedirect(string $key, string $message): RedirectResponse
    {
        $tenantParameter = ['tenant' => $this->resolveTenantId(request())];

        return redirect()
            ->to(route('billing.index', $tenantParameter, false))
            ->with($key, $message);
    }
}
