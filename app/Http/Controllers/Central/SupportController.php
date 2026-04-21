<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $query = SupportRequest::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('tenant_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('requester_email', 'like', "%{$search}%");
            });
        }

        $supportRequests = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => SupportRequest::count(),
            'open' => SupportRequest::where('status', 'open')->count(),
            'in_progress' => SupportRequest::where('status', 'in_progress')->count(),
            'resolved' => SupportRequest::where('status', 'resolved')->count(),
        ];

        return view('central.support.index', compact('supportRequests', 'stats'));
    }

    public function show(SupportRequest $supportRequest): View
    {
        return view('central.support.show', compact('supportRequest'));
    }

    public function updateStatus(Request $request, SupportRequest $supportRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved'],
        ]);

        $supportRequest->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ]);

        return back()->with('success', 'Support request status updated successfully.');
    }
}
