<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\LoanType;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewReports', tenant());

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'report_type' => ['nullable', 'string', 'max:50'],
        ]);

        $branchId = $filters['branch_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $reportType = $filters['report_type'] ?? 'summary';

        $loanBaseQuery = Loan::query()
            ->with(['member', 'branch', 'loanType'])
            ->when($branchId !== null, static fn (Builder $query): Builder => $query->where('branch_id', $branchId));

        $releasedLoansQuery = (clone $loanBaseQuery)
            ->when($dateFrom !== null, static fn (Builder $query): Builder => $query->whereDate('release_date', '>=', $dateFrom))
            ->when($dateTo !== null, static fn (Builder $query): Builder => $query->whereDate('release_date', '<=', $dateTo));

        $paymentBaseQuery = LoanPayment::query()
            ->with(['loan.member', 'loan.branch', 'user'])
            ->when($branchId !== null, static fn (Builder $query): Builder => $query->whereHas('loan', static fn (Builder $loanQuery): Builder => $loanQuery->where('branch_id', $branchId)))
            ->when($dateFrom !== null, static fn (Builder $query): Builder => $query->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo !== null, static fn (Builder $query): Builder => $query->whereDate('payment_date', '<=', $dateTo));

        $overdueLoansQuery = (clone $loanBaseQuery)
            ->where(function (Builder $query): void {
                $query->where('status', 'overdue')
                    ->orWhere(function (Builder $loanQuery): void {
                        $loanQuery->whereDate('due_date', '<', today())
                            ->where('status', '!=', 'fully_paid');
                    });
            });

        if ($dateFrom !== null || $dateTo !== null) {
            $overdueLoansQuery->where(function (Builder $query) use ($dateFrom, $dateTo): void {
                if ($dateFrom !== null) {
                    $query->whereDate('due_date', '>=', $dateFrom);
                }

                if ($dateTo !== null) {
                    $query->whereDate('due_date', '<=', $dateTo);
                }
            });
        }

        $totalLoansReleasedCount = (clone $releasedLoansQuery)->count();
        $totalLoansReleasedAmount = round((float) (clone $releasedLoansQuery)->sum('principal_amount'), 2);
        $totalCollections = round((float) (clone $paymentBaseQuery)->sum('amount'), 2);
        $totalOutstandingBalance = round((float) (clone $loanBaseQuery)->sum('outstanding_balance'), 2);
        $totalOverdueLoans = (clone $overdueLoansQuery)->count();
        $fullyPaidLoansCount = (clone $loanBaseQuery)
            ->where('status', 'fully_paid')
            ->count();

        $interestIncome = round((float) LoanSchedule::query()
            ->when($branchId !== null, static fn (Builder $query): Builder => $query->whereHas('loan', static fn (Builder $loanQuery): Builder => $loanQuery->where('branch_id', $branchId)))
            ->where('status', 'paid')
            ->when($dateFrom !== null, static fn (Builder $query): Builder => $query->whereDate('paid_at', '>=', $dateFrom))
            ->when($dateTo !== null, static fn (Builder $query): Builder => $query->whereDate('paid_at', '<=', $dateTo))
            ->sum('interest_portion'), 2);

        $loanBreakdown = LoanType::query()
            ->orderBy('name')
            ->get()
            ->map(function (LoanType $loanType) use ($branchId, $dateFrom, $dateTo): array {
                $loansQuery = $loanType->loans()
                    ->when($branchId !== null, static fn (Builder $query): Builder => $query->where('branch_id', $branchId))
                    ->when($dateFrom !== null, static fn (Builder $query): Builder => $query->whereDate('release_date', '>=', $dateFrom))
                    ->when($dateTo !== null, static fn (Builder $query): Builder => $query->whereDate('release_date', '<=', $dateTo));

                return [
                    'name' => $loanType->name,
                    'count' => $loansQuery->count(),
                    'total_principal' => round((float) $loansQuery->sum('principal_amount'), 2),
                    'total_payable' => round((float) $loansQuery->sum('total_payable'), 2),
                ];
            })
            ->filter(static fn (array $item): bool => $item['count'] > 0)
            ->values();

        $collectionsByMonth = (clone $paymentBaseQuery)
            ->get()
            ->groupBy(static fn (LoanPayment $payment): string => $payment->payment_date?->format('Y-m') ?? 'unknown')
            ->sortKeys()
            ->map(static function ($payments, string $month): array {
                $monthLabel = $month === 'unknown'
                    ? 'Unknown'
                    : \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y');

                return [
                    'month' => $monthLabel,
                    'payments_count' => $payments->count(),
                    'total_collected' => round((float) $payments->sum('amount'), 2),
                ];
            })
            ->values();

        $overdueLoans = (clone $overdueLoansQuery)
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        $topBorrowers = Member::query()
            ->when($branchId !== null, static fn (Builder $query): Builder => $query->where('branch_id', $branchId))
            ->withCount([
                'loans as active_loans_count' => static function (Builder $query) use ($branchId, $dateFrom, $dateTo): void {
                    $query->whereIn('status', ['active', 'overdue', 'restructured'])
                        ->when($branchId !== null, static fn (Builder $branchQuery): Builder => $branchQuery->where('branch_id', $branchId))
                        ->when($dateFrom !== null, static fn (Builder $dateQuery): Builder => $dateQuery->whereDate('release_date', '>=', $dateFrom))
                        ->when($dateTo !== null, static fn (Builder $dateQuery): Builder => $dateQuery->whereDate('release_date', '<=', $dateTo));
                },
            ])
            ->withSum([
                'loans as total_outstanding' => static function (Builder $query) use ($branchId, $dateFrom, $dateTo): void {
                    $query->when($branchId !== null, static fn (Builder $branchQuery): Builder => $branchQuery->where('branch_id', $branchId))
                        ->when($dateFrom !== null, static fn (Builder $dateQuery): Builder => $dateQuery->whereDate('release_date', '>=', $dateFrom))
                        ->when($dateTo !== null, static fn (Builder $dateQuery): Builder => $dateQuery->whereDate('release_date', '<=', $dateTo));
                },
            ], 'outstanding_balance')
            ->get()
            ->filter(static fn (Member $member): bool => (float) ($member->total_outstanding ?? 0) > 0)
            ->sortByDesc(static fn (Member $member): float => (float) ($member->total_outstanding ?? 0))
            ->take(10)
            ->values();

        $branches = Branch::query()->orderBy('name')->get();

        return view('reports.index', compact(
            'filters',
            'reportType',
            'branches',
            'totalLoansReleasedCount',
            'totalLoansReleasedAmount',
            'totalCollections',
            'totalOutstandingBalance',
            'totalOverdueLoans',
            'interestIncome',
            'fullyPaidLoansCount',
            'loanBreakdown',
            'collectionsByMonth',
            'overdueLoans',
            'topBorrowers',
        ));
    }
}
