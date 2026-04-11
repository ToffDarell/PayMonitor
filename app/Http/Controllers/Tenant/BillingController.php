<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        $tenant = tenant();

        abort_if($tenant === null, 404, 'Tenant context could not be resolved.');

        $invoices = BillingInvoice::query()
            ->where('tenant_id', (string) $tenant->id)
            ->latest('due_date')
            ->latest('id')
            ->get();

        return view('billing.index', compact('invoices'));
    }
}
