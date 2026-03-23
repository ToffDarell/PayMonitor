<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
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
        $baseDate = $tenant->subscription_due_at instanceof CarbonInterface
            && $tenant->subscription_due_at->greaterThan(today())
                ? $tenant->subscription_due_at
                : today();

        $tenant->subscription_due_at = $baseDate->copy()->addDays(30);
        $tenant->status = 'active';
        $tenant->save();

        return back()->with('success', 'Payment recorded successfully.');
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
