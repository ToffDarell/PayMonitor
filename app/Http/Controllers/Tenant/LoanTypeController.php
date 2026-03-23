<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreLoanTypeRequest;
use App\Models\LoanType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoanTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LoanType::class);

        $loanTypes = LoanType::query()
            ->withCount('loans')
            ->latest()
            ->paginate(15);

        return view('loan-types.index', compact('loanTypes'));
    }

    public function create(): View
    {
        $this->authorize('create', LoanType::class);

        return view('loan-types.create');
    }

    public function store(StoreLoanTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', LoanType::class);

        $loanType = LoanType::query()->create([
            ...$request->validated(),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect('/loan-types/'.$loanType->id)->with('success', 'Loan type created successfully.');
    }

    public function show(string $tenant, LoanType $loanType): View
    {
        $this->authorize('view', $loanType);

        $loans = $loanType->loans()
            ->with(['member', 'branch'])
            ->latest()
            ->get();

        return view('loan-types.show', compact('loanType', 'loans'));
    }

    public function edit(string $tenant, LoanType $loanType): View
    {
        $this->authorize('update', $loanType);

        return view('loan-types.edit', compact('loanType'));
    }

    public function update(StoreLoanTypeRequest $request, string $tenant, LoanType $loanType): RedirectResponse
    {
        $this->authorize('update', $loanType);

        $loanType->update([
            ...$request->validated(),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect('/loan-types/'.$loanType->id)->with('success', 'Loan type updated successfully.');
    }

    public function destroy(string $tenant, LoanType $loanType): RedirectResponse
    {
        $this->authorize('delete', $loanType);

        $loanType->delete();

        return redirect('/loan-types')->with('success', 'Loan type deleted successfully.');
    }
}
