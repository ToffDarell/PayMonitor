<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreMemberRequest;
use App\Models\Branch;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $branches = Branch::query()->orderBy('name')->get();

        $members = Member::query()
            ->with('branch')
            ->withCount([
                'loans as active_loans_count' => static fn ($query) => $query->whereIn('status', ['active', 'overdue', 'restructured']),
            ])
            ->withSum('loans as outstanding_balance_sum', 'outstanding_balance')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));

                $query->where(function ($memberQuery) use ($search): void {
                    $memberQuery->where('member_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('branch'), fn ($query) => $query->where('branch_id', $request->integer('branch')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('members.index', compact('members', 'branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Member::class);

        $branches = Branch::query()->orderBy('name')->get();

        return view('members.create', compact('branches'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $this->authorize('create', Member::class);

        $member = Member::query()->create([
            ...$request->validated(),
            'member_number' => $this->generateMemberNumber(),
            'is_active' => (bool) $request->boolean('is_active', true),
        ]);

        return redirect('/members/'.$member->id)->with('success', 'Member created successfully.');
    }

    public function show(string $tenant, Member $member): View
    {
        $this->authorize('view', $member);

        $member->load('branch');
        $loanHistory = $member->loans()
            ->with(['loanType', 'branch'])
            ->latest('release_date')
            ->get();

        $activeLoans = $loanHistory->whereIn('status', ['active', 'overdue', 'restructured']);
        $totalOutstanding = (float) $loanHistory->sum('outstanding_balance');
        $totalBorrowed = (float) $loanHistory->sum('principal_amount');
        $totalPaid = (float) $loanHistory->sum('amount_paid');

        return view('members.show', compact(
            'member',
            'activeLoans',
            'loanHistory',
            'totalOutstanding',
            'totalBorrowed',
            'totalPaid',
        ));
    }

    public function edit(string $tenant, Member $member): View
    {
        $this->authorize('update', $member);

        $branches = Branch::query()->orderBy('name')->get();

        return view('members.edit', compact('member', 'branches'));
    }

    public function update(StoreMemberRequest $request, string $tenant, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $member->update($request->validated());
        $member->forceFill([
            'is_active' => $request->boolean('is_active', true),
        ])->save();

        return redirect('/members/'.$member->id)->with('success', 'Member updated successfully.');
    }

    public function destroy(string $tenant, Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $member->delete();

        return redirect('/members')->with('success', 'Member deleted successfully.');
    }

    protected function generateMemberNumber(): string
    {
        do {
            $memberNumber = sprintf('MBR-%s-%04d', now()->format('Ymd'), random_int(0, 9999));
        } while (Member::query()->where('member_number', $memberNumber)->exists());

        return $memberNumber;
    }
}
