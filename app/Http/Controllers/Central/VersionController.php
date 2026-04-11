<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VersionController extends Controller
{
    public function __construct()
    {
        $this->middleware(static function ($request, $next) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $versions = AppVersion::query()
            ->withCount('acknowledgements')
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get();

        $totalTenants = Tenant::query()->count();
        $latestActiveVersion = AppVersion::latestActive();

        return view('central.versions.index', compact('versions', 'totalTenants', 'latestActiveVersion'));
    }

    public function create(): View
    {
        return view('central.versions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'version_number' => ['required', 'string', 'max:50', 'regex:/^\d+(?:\.\d+){1,2}$/', Rule::unique('app_versions', 'version_number')],
            'title' => ['required', 'string', 'max:255'],
            'changelog' => ['required', 'string'],
            'released_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active');
        $releasedAt = filled($validated['released_at'] ?? null)
            ? Carbon::parse((string) $validated['released_at'])->startOfDay()
            : ($isActive ? now() : null);

        DB::transaction(function () use ($validated, $isActive, $releasedAt): void {
            if ($isActive) {
                AppVersion::query()->where('is_active', true)->update(['is_active' => false]);
            }

            AppVersion::query()->create([
                'version_number' => $validated['version_number'],
                'title' => $validated['title'],
                'changelog' => trim((string) $validated['changelog']),
                'is_active' => $isActive,
                'released_at' => $releasedAt,
            ]);
        });

        return redirect('/central/versions')->with( 
            'success',
            $isActive ? 'App version published successfully.' : 'App version saved successfully.',
        );
    }
}
