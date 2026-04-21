<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\User;
use App\Support\TenantPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            $request->user()?->hasTenantPermission(TenantPermissions::COLLECTIONS_VIEW, ['tenant_admin', 'branch_manager', 'loan_officer', 'cashier', 'viewer']),
            403,
            'This action is unauthorized.'
        );

        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'collector' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(['active', 'overdue', 'fully_paid', 'restructured'])],
        ]);

        $monthStart = today()->startOfMonth();
        $monthEnd = today()->endOfMonth();
        $weekEnd = today()->copy()->addDays(7);

        $totalDueQuery = LoanSchedule::query()
            ->whereBetween('due_date', [$monthStart, $monthEnd])
            ->where('status', '!=', 'paid')
            ->whereHas('loan', function ($query) use ($filters): void {
                $query->whereIn('status', ['active', 'overdue', 'restructured'])
                    ->when($filters['branch_id'] ?? null, static fn ($loanQuery, int $branchId) => $loanQuery->where('branch_id', $branchId))
                    ->when($filters['status'] ?? null, static fn ($loanQuery, string $status) => $loanQuery->where('status', $status));
            });

        $paymentsThisMonthQuery = LoanPayment::query()
            ->whereBetween('payment_date', [$monthStart, $monthEnd]);

        $this->applyPaymentFilters($paymentsThisMonthQuery, $filters);

        $overdueLoansQuery = Loan::query()
            ->with(['member', 'branch'])
            ->where('status', 'overdue')
            ->whereDate('due_date', '<', today())
            ->when($filters['branch_id'] ?? null, static fn ($query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['status'] ?? null, static fn ($query, string $status) => $query->where('status', $status));

        $recentCollectionsQuery = LoanPayment::query()
            ->with(['loan.member', 'user'])
            ->latest('created_at');

        $this->applyPaymentFilters($recentCollectionsQuery, $filters);

        $dueThisWeekQuery = LoanSchedule::query()
            ->with(['loan.member', 'loan.branch'])
            ->whereBetween('due_date', [today(), $weekEnd])
            ->where('status', '!=', 'paid')
            ->whereHas('loan', function ($query) use ($filters): void {
                $query->whereIn('status', ['active', 'overdue', 'restructured'])
                    ->when($filters['branch_id'] ?? null, static fn ($loanQuery, int $branchId) => $loanQuery->where('branch_id', $branchId))
                    ->when($filters['status'] ?? null, static fn ($loanQuery, string $status) => $loanQuery->where('status', $status));
            })
            ->orderBy('due_date')
            ->orderBy('period_number');

        $totalDue = round((float) (clone $totalDueQuery)->sum('amount_due'), 2);
        $totalCollected = round((float) (clone $paymentsThisMonthQuery)->sum('amount'), 2);
        $collectionRate = $totalDue > 0
            ? round(($totalCollected / $totalDue) * 100, 1)
            : 0.0;
        $overdueLoans = (clone $overdueLoansQuery)->count();
        $overdueAmount = round((float) (clone $overdueLoansQuery)->sum('outstanding_balance'), 2);

        $collectedTodayQuery = LoanPayment::query()->whereDate('payment_date', today());
        $this->applyPaymentFilters($collectedTodayQuery, $filters);
        $collectedToday = round((float) $collectedTodayQuery->sum('amount'), 2);

        $recentCollections = $recentCollectionsQuery
            ->limit(20)
            ->get();

        $dueThisWeek = $dueThisWeekQuery
            ->limit(12)
            ->get();

        $branches = Branch::query()->orderBy('name')->get();
        $collectors = User::query()->orderBy('name')->get();

        return view('collections.index', compact(
            'filters',
            'branches',
            'collectors',
            'totalDue',
            'totalCollected',
            'collectionRate',
            'overdueLoans',
            'overdueAmount',
            'collectedToday',
            'recentCollections',
            'dueThisWeek',
        ));
    }

    private function applyPaymentFilters($query, array $filters): void
    {
        $query
            ->when($filters['branch_id'] ?? null, static function ($paymentQuery, int $branchId): void {
                $paymentQuery->whereHas('loan', static fn ($loanQuery) => $loanQuery->where('branch_id', $branchId));
            })
            ->when($filters['collector'] ?? null, static fn ($paymentQuery, int $collectorId) => $paymentQuery->where('user_id', $collectorId))
            ->when($filters['status'] ?? null, static function ($paymentQuery, string $status): void {
                $paymentQuery->whereHas('loan', static fn ($loanQuery) => $loanQuery->where('status', $status));
            })
            ->when($filters['date_from'] ?? null, static fn ($paymentQuery, string $dateFrom) => $paymentQuery->whereDate('payment_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, static fn ($paymentQuery, string $dateTo) => $paymentQuery->whereDate('payment_date', '<=', $dateTo));
    }
}
