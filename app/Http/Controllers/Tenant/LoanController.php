<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreLoanRequest;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Member;
use App\Services\AuditService;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function __construct(
        private LoanService $loanService,
        private AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Loan::class);

        $loans = Loan::query()
            ->with(['member', 'branch', 'loanType'])
            ->when($request->filled('branch'), fn ($query) => $query->where('branch_id', $request->integer('branch')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('loan_type'), fn ($query) => $query->where('loan_type_id', $request->integer('loan_type')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('release_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('release_date', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::query()->orderBy('name')->get();
        $loanTypes = LoanType::query()->orderBy('name')->get();

        return view('loans.index', compact('loans', 'branches', 'loanTypes'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Loan::class);

        $members = Member::query()->where('is_active', true)->orderBy('last_name')->get();
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();
        $loanTypes = LoanType::query()->where('is_active', true)->orderBy('name')->get();
        $prefilledMember = null;

        if ($request->filled('member_id')) {
            $prefilledMember = $members->firstWhere('id', $request->integer('member_id'));
        }

        return view('loans.create', compact('members', 'branches', 'loanTypes', 'prefilledMember'));
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $this->authorize('create', Loan::class);

        $loan = DB::transaction(function () use ($request): Loan {
            $validated = $request->validated();
            $loanType = LoanType::query()->findOrFail($validated['loan_type_id']);
            $releaseDate = $request->date('release_date') ?? today();

            $computed = $this->loanService->computeLoan([
                'principal_amount' => $validated['principal_amount'],
                'interest_rate' => $loanType->interest_rate,
                'interest_type' => $loanType->interest_type,
                'term_months' => $validated['term_months'],
            ]);

            $loan = Loan::query()->create([
                'member_id' => $validated['member_id'],
                'branch_id' => $validated['branch_id'],
                'user_id' => auth()->id(),
                'loan_type_id' => $loanType->id,
                'loan_number' => $this->loanService->generateLoanNumber(),
                'principal_amount' => $validated['principal_amount'],
                'interest_rate' => $loanType->interest_rate,
                'interest_type' => $loanType->interest_type,
                'term_months' => $validated['term_months'],
                'total_interest' => $computed['total_interest'],
                'total_payable' => $computed['total_payable'],
                'monthly_payment' => $computed['monthly_payment'],
                'amount_paid' => 0,
                'outstanding_balance' => $computed['outstanding_balance'],
                'status' => 'active',
                'release_date' => $releaseDate->toDateString(),
                'due_date' => $releaseDate->copy()->addMonthsNoOverflow((int) $validated['term_months'])->toDateString(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->loanService->generateAmortizationSchedule($loan);
            $this->auditService->log('created', $loan, [], $loan->fresh()->toArray());

            return $loan;
        });

        return redirect('/loans/'.$loan->id)->with('success', 'Loan created successfully.');
    }

    public function show(string $tenant, Loan $loan): View
    {
        $this->authorize('view', $loan);

        $loan->load([
            'member',
            'branch',
            'user',
            'loanType',
            'documents.uploadedBy',
            'loanPayments.user',
            'loanSchedules',
        ]);

        return view('loans.show', compact('loan'));
    }

    public function edit(string $tenant, Loan $loan): View|RedirectResponse
    {
        $this->authorize('update', $loan);

        if ($loan->status === 'fully_paid') {
            return redirect('/loans/'.$loan->id)->with('error', 'Fully paid loans can no longer be edited.');
        }

        $loan->load(['member', 'branch', 'user', 'loanType'])->loadCount('loanPayments');

        return view('loans.edit', compact('loan'));
    }

    public function update(Request $request, string $tenant, Loan $loan): RedirectResponse
    {
        $this->authorize('update', $loan);

        if ($loan->status === 'fully_paid') {
            return redirect('/loans/'.$loan->id)->with('error', 'Fully paid loans can no longer be edited.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'overdue', 'restructured', 'fully_paid'])],
        ]);

        $oldValues = $loan->toArray();

        $loan->update([
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
        ]);

        $this->auditService->log('updated', $loan, $oldValues, $loan->fresh()->toArray());

        return redirect('/loans/'.$loan->id)->with('success', 'Loan updated successfully.');
    }

    public function destroy(string $tenant, Loan $loan): RedirectResponse
    {
        $this->authorize('delete', $loan);

        if ($loan->status !== 'active' || $loan->loanPayments()->exists()) {
            return redirect('/loans/'.$loan->id)->with('error', 'Only active loans without payments can be deleted.');
        }

        DB::transaction(function () use ($loan): void {
            $loan->loanSchedules()->delete();
            $loan->delete();
        });

        return redirect('/loans')->with('success', 'Loan deleted successfully.');
    }

    public function computePreview(Request $request): JsonResponse
    {
        $this->authorize('computePreview', Loan::class);

        $validated = $request->validate([
            'principal' => ['required', 'numeric', 'min:1'],
            'rate' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(['flat', 'diminishing'])],
            'term_months' => ['required', 'integer', 'min:1'],
        ]);

        $computed = $this->loanService->computeLoan([
            'principal_amount' => $validated['principal'],
            'interest_rate' => $validated['rate'],
            'interest_type' => $validated['type'],
            'term_months' => $validated['term_months'],
        ]);

        return response()->json([
            'total_interest' => $computed['total_interest'],
            'total_payable' => $computed['total_payable'],
            'monthly_payment' => $computed['monthly_payment'],
        ]);
    }
}
