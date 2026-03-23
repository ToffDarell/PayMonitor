<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Member;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewDashboard', tenant());

        $activeLoansCount = Loan::query()
            ->where('status', 'active')
            ->count();

        $totalOutstandingBalance = (float) Loan::query()
            ->where('status', 'active')
            ->sum('outstanding_balance');

        $overdueLoansCount = Loan::query()
            ->where(function ($query): void {
                $query->where('status', 'overdue')
                    ->orWhere(function ($loanQuery): void {
                        $loanQuery->whereDate('due_date', '<', today())
                            ->where('status', '!=', 'fully_paid');
                    });
            })
            ->count();

        $totalMembersCount = Member::query()->count();

        $totalPaymentsThisMonth = (float) LoanPayment::query()
            ->whereBetween('payment_date', [today()->startOfMonth(), today()->endOfMonth()])
            ->sum('amount');

        $loanTypesCount = LoanType::query()
            ->where('is_active', true)
            ->count();

        $recentLoans = Loan::query()
            ->with(['member', 'loanType'])
            ->latest()
            ->limit(10)
            ->get();

        $topOverdueLoans = Loan::query()
            ->with(['member', 'loanType'])
            ->where(function ($query): void {
                $query->where('status', 'overdue')
                    ->orWhere(function ($loanQuery): void {
                        $loanQuery->whereDate('due_date', '<', today())
                            ->where('status', '!=', 'fully_paid');
                    });
            })
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'activeLoansCount',
            'totalOutstandingBalance',
            'overdueLoansCount',
            'totalMembersCount',
            'totalPaymentsThisMonth',
            'loanTypesCount',
            'recentLoans',
            'topOverdueLoans',
        ));
    }
}
