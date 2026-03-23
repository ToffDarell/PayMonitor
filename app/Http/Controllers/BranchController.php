<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;

class BranchController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $branches = Branch::where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->paginate(15);

        return view('branches.index', compact('branches'));
    }

    public function create(): \Illuminate\View\View
    {
        return view('branches.create');
    }

    public function store(BranchRequest $request): \Illuminate\Http\RedirectResponse
    {
        Branch::create([
            ...$request->validated(),
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch): \Illuminate\View\View
    {
        $this->authorizeTenant($branch);

        return view('branches.show', compact('branch'));
    }

    public function edit(Branch $branch): \Illuminate\View\View
    {
        $this->authorizeTenant($branch);

        return view('branches.edit', compact('branch'));
    }

    public function update(BranchRequest $request, Branch $branch): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeTenant($branch);
        $branch->update($request->validated());

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeTenant($branch);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted.');
    }

    private function authorizeTenant(Branch $branch): void
    {
        abort_if($branch->tenant_id !== auth()->user()->tenant_id, 403);
    }
}