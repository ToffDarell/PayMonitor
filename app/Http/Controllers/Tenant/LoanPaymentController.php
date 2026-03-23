<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreLoanPaymentRequest;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoanPaymentController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LoanPayment::class);

        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'member_search' => ['nullable', 'string', 'max:255'],
        ]);

        $memberSearch = trim((string) ($filters['member_search'] ?? ''));

        $paymentsQuery = LoanPayment::query()
            ->with(['loan.member', 'user'])
            ->when($filters['branch_id'] ?? null, static function ($query, int $branchId): void {
                $query->whereHas('loan', static function ($loanQuery) use ($branchId): void {
                    $loanQuery->where('branch_id', $branchId);
                });
            })
            ->when($filters['date_from'] ?? null, static fn ($query, string $dateFrom) => $query->whereDate('payment_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, static fn ($query, string $dateTo) => $query->whereDate('payment_date', '<=', $dateTo))
            ->when($memberSearch !== '', static function ($query) use ($memberSearch): void {
                $query->whereHas('loan.member', static function ($memberQuery) use ($memberSearch): void {
                    $memberQuery->where(function ($nestedQuery) use ($memberSearch): void {
                        $nestedQuery->where('member_number', 'like', "%{$memberSearch}%")
                            ->orWhere('first_name', 'like', "%{$memberSearch}%")
                            ->orWhere('last_name', 'like', "%{$memberSearch}%")
                            ->orWhere('middle_name', 'like', "%{$memberSearch}%");
                    });
                });
            });

        $payments = (clone $paymentsQuery)
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::query()->orderBy('name')->get();
        $totalCollected = round((float) $paymentsQuery->sum('amount'), 2);

        return view('loan-payments.index', compact('payments', 'branches', 'filters', 'totalCollected'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', LoanPayment::class);

        $validated = $request->validate([
            'loan' => ['required', 'integer', Rule::exists('loans', 'id')],
        ]);

        $loan = Loan::query()
            ->with(['member', 'branch', 'loanType', 'user'])
            ->findOrFail($validated['loan']);

        return view('loan-payments.create', compact('loan'));
    }

    public function store(StoreLoanPaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', LoanPayment::class);

        $validated = $request->validated();

        $loan = DB::transaction(function () use ($validated): Loan {
            $loan = Loan::query()
                ->with('loanSchedules')
                ->lockForUpdate()
                ->findOrFail($validated['loan_id']);

            $paymentAmount = (float) $validated['amount'];
            $outstandingBalance = (float) $loan->outstanding_balance;

            if ($paymentAmount > $outstandingBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount cannot exceed the outstanding balance.',
                ]);
            }

            $payment = LoanPayment::query()->create([
                'loan_id' => $loan->id,
                'user_id' => auth()->id(),
                'amount' => $paymentAmount,
                'payment_date' => $validated['payment_date'],
                'period_covered' => $validated['period_covered'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $oldValues = $loan->toArray();

            $amountPaid = round((float) $loan->loanPayments()->sum('amount'), 2);
            $remainingBalance = round(max((float) $loan->total_payable - $amountPaid, 0), 2);

            $loan->forceFill([
                'amount_paid' => $amountPaid,
                'outstanding_balance' => $remainingBalance,
                'status' => $remainingBalance <= 0 ? 'fully_paid' : $loan->status,
            ])->save();

            $this->syncSchedules($loan);
            $this->auditService->log('created', $payment, [], $payment->toArray());
            $this->auditService->log('updated', $loan, $oldValues, $loan->fresh()->toArray());

            return $loan;
        });

        return redirect('/loans/'.$loan->id)->with('success', 'Payment recorded successfully.');
    }

    protected function syncSchedules(Loan $loan): void
    {
        $coveredAmount = (float) $loan->amount_paid;

        $loan->loanSchedules()
            ->orderBy('period_number')
            ->get()
            ->each(function (LoanSchedule $schedule) use (&$coveredAmount): void {
                $amountDue = (float) $schedule->amount_due;

                if ($coveredAmount >= $amountDue) {
                    $schedule->forceFill([
                        'status' => 'paid',
                        'paid_at' => $schedule->paid_at ?? now(),
                    ])->save();
                    $coveredAmount = round($coveredAmount - $amountDue, 2);

                    return;
                }

                $schedule->forceFill([
                    'status' => $schedule->due_date !== null && $schedule->due_date->lt(today()) ? 'overdue' : 'pending',
                    'paid_at' => null,
                ])->save();
            });
    }
}
