<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreBranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);

        $branches = Branch::query()
            ->withCount([
                'users as staff_count',
                'loans as active_loans_count' => static fn ($query) => $query->where('status', 'active'),
            ])
            ->latest()
            ->paginate(15);

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        return view('branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);

        $branch = Branch::query()->create($request->validated());

        return redirect('/branches/'.$branch->id)->with('success', 'Branch created successfully.');
    }

    public function show(string $tenant, Branch $branch): View
    {
        $this->authorize('view', $branch);

        $branch->load(['users.roles', 'members', 'loans']);
        $staffCount = $branch->users->count();
        $membersCount = $branch->members->count();
        $activeLoansCount = $branch->loans->where('status', 'active')->count();
        $outstandingBalance = round((float) $branch->loans->sum('outstanding_balance'), 2);

        return view('branches.show', compact('branch', 'staffCount', 'membersCount', 'activeLoansCount', 'outstandingBalance'));
    }

    public function edit(string $tenant, Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('branches.edit', compact('branch'));
    }

    public function update(StoreBranchRequest $request, string $tenant, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $branch->update($request->validated());

        return redirect('/branches/'.$branch->id)->with('success', 'Branch updated successfully.');
    }

    public function destroy(string $tenant, Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        return redirect('/branches')->with('success', 'Branch deleted successfully.');
    }
}
