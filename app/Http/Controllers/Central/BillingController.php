<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Mail\TenantInvoiceMail;
use App\Mail\TenantReceiptMail;
use App\Models\BillingInvoice;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware(static function ($request, $next) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $tenants = Tenant::query()
            ->with('plan')
            ->orderBy('name')
            ->get();

        $invoicesQuery = BillingInvoice::query()
            ->with(['tenant.plan'])
            ->when(
                $request->filled('tenant_id'),
                fn ($query) => $query->where('tenant_id', $request->string('tenant_id')->toString()),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when(
                $request->filled('month'),
                fn ($query) => $query->whereMonth('due_date', (int) $request->integer('month')),
            )
            ->when(
                $request->filled('date_from'),
                fn ($query) => $query->whereDate('due_date', '>=', (string) $request->string('date_from')),
            )
            ->when(
                $request->filled('date_to'),
                fn ($query) => $query->whereDate('due_date', '<=', (string) $request->string('date_to')),
            );

        $summaryBaseQuery = clone $invoicesQuery;
        $summary = [
            'total_invoiced' => (float) (clone $summaryBaseQuery)->sum('amount'),
            'total_paid' => (float) (clone $summaryBaseQuery)->paid()->sum('amount'),
            'total_unpaid' => (float) (clone $summaryBaseQuery)->whereIn('status', ['unpaid', 'pending_verification'])->sum('amount'),
            'total_overdue' => (float) (clone $summaryBaseQuery)->overdue()->sum('amount'),
        ];

        $invoices = $invoicesQuery
            ->latest('due_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('central.billing.index', compact('invoices', 'summary', 'tenants'));
    }

    public function show(BillingInvoice $invoice): View
    {
        $invoice->loadMissing('tenant.plan');

        return view('central.billing.show', compact('invoice'));
    }

    public function sendInvoice(Tenant $tenant): RedirectResponse
    {
        $tenant->loadMissing('plan');

        $invoice = BillingInvoice::syncOpenInvoiceForTenant(
            $tenant,
            'Manually generated billing invoice.',
        ) ?? BillingInvoice::firstOrCreateForTenantCycle(
            $tenant,
            $tenant->subscription_due_at ?? today(),
            'Manually generated billing invoice.',
        );

        Mail::to($tenant->email)->send(new TenantInvoiceMail($tenant, $invoice, 'invoice'));

        return back()->with('success', "Invoice {$invoice->invoice_number} sent successfully.");
    }

    public function markPaid(BillingInvoice $invoice): RedirectResponse
    {
        $invoice->markPaidAndRenewTenant();
        $invoice->loadMissing('tenant.plan');
        $nextDueDate = $invoice->tenant->subscription_due_at?->format('M d, Y') ?? 'Not set';

        Mail::to($invoice->tenant->email)->send(new TenantReceiptMail(
            $invoice->tenant,
            $invoice,
            $invoice->tenant->subscription_due_at,
        ));

        return back()->with('success', "Invoice {$invoice->invoice_number} marked as paid and receipt sent. New due date: {$nextDueDate}.");
    }

    public function sendReceipt(BillingInvoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'paid') {
            return back()->with('error', 'Only paid invoices can send a receipt.');
        }

        $invoice->loadMissing('tenant.plan');

        Mail::to($invoice->tenant->email)->send(new TenantReceiptMail(
            $invoice->tenant,
            $invoice,
            $invoice->tenant->subscription_due_at,
        ));

        return back()->with('success', "Receipt for {$invoice->invoice_number} sent successfully.");
    }
}
