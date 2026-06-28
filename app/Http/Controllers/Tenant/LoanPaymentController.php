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
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoanPaymentController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly SmsService $smsService,
    ) {}

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

            $periodsCovered = $this->applyPaymentToSchedules($loan, $paymentAmount);

            $payment = LoanPayment::query()->create([
                'loan_id' => $loan->id,
                'user_id' => auth()->id(),
                'amount' => $paymentAmount,
                'payment_date' => $validated['payment_date'],
                'period_covered' => $periodsCovered,
                'notes' => $validated['notes'] ?? null,
            ]);

            $oldValues = $loan->toArray();

            $amountPaid = round((float) $loan->loanPayments()->sum('amount'), 2);
            $remainingBalance = round(max((float) $loan->outstanding_balance - $paymentAmount, 0), 2);
            $newStatus = $remainingBalance <= 0 ? 'fully_paid' : ($loan->isOverdue() ? 'overdue' : $loan->status);

            $loan->forceFill([
                'amount_paid' => $amountPaid,
                'outstanding_balance' => $remainingBalance,
                'status' => $newStatus,
            ])->save();

            if ($newStatus === 'fully_paid') {
                $loan->loanSchedules()
                    ->whereIn('status', ['pending', 'partially_paid'])
                    ->where('balance', '>', 0)
                    ->update([
                        'amount_paid' => DB::raw('amount_due'),
                        'balance' => 0,
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
            }

            $this->auditService->log('created', $payment, [], $payment->toArray());
            $this->auditService->log('updated', $loan, $oldValues, $loan->fresh()->toArray());

            return $loan;
        });

        $member = $loan->member;
        if (filled($member?->phone)) {
            $this->smsService->sendToMember($member, $this->smsService->paymentConfirmation($loan, (float) $validated['amount'], $member));
        }

        return redirect('/loans/'.$loan->id)->with('success', 'Payment recorded successfully.');
    }

    protected function applyPaymentToSchedules(Loan $loan, float $paymentAmount): string
    {
        $remainingPayment = $paymentAmount;
        $touchedPeriods = [];

        $schedules = $loan->loanSchedules()
            ->orderBy('period_number')
            ->get();

        foreach ($schedules as $schedule) {
            if ($remainingPayment <= 0) {
                break;
            }

            $amountDue = (float) $schedule->amount_due;
            $amountPaidSoFar = (float) $schedule->amount_paid;
            $remainingOnPeriod = round($amountDue - $amountPaidSoFar, 2);

            if ($remainingOnPeriod <= 0) {
                continue;
            }

            if ($remainingPayment >= $remainingOnPeriod) {
                $schedule->forceFill([
                    'amount_paid' => $amountDue,
                    'balance' => 0,
                    'status' => 'paid',
                    'paid_at' => $schedule->paid_at ?? now(),
                ])->save();

                $remainingPayment = round($remainingPayment - $remainingOnPeriod, 2);
                $touchedPeriods[] = 'Period '.$schedule->period_number;

                continue;
            }

            $newAmountPaid = round($amountPaidSoFar + $remainingPayment, 2);
            $schedule->forceFill([
                'amount_paid' => $newAmountPaid,
                'balance' => round($amountDue - $newAmountPaid, 2),
                'status' => 'partially_paid',
                'paid_at' => null,
            ])->save();

            $touchedPeriods[] = 'Period '.$schedule->period_number.' (Partial)';
            $remainingPayment = 0;
        }

        if (count($touchedPeriods) === 0) {
            return 'Period 0';
        }

        return implode(', ', $touchedPeriods);
    }
}
