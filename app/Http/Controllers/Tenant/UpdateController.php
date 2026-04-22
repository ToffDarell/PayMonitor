<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantSelfUpdateService;
use App\Services\TenantUpdateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateController extends Controller
{
    public function __construct(
        private TenantUpdateService $tenantUpdateService,
        private TenantSelfUpdateService $selfUpdateService
    ) {}

    public function index(Request $request)
    {
        $tenantId = $request->route('tenant');
        
        $current = $this->tenantUpdateService->getCurrentRelease($tenantId);
        $available = $this->tenantUpdateService->getAvailableUpdates($tenantId);
        $required = $this->tenantUpdateService->getRequiredUpdate($tenantId);

        return view('tenant.updates.index', compact('current', 'available', 'required'));
    }

    public function apply(Request $request)
    {
        $centralConnection = (string) config('tenancy.database.central_connection', config('database.default'));

        $request->validate([
            'release_id' => [
                'required',
                Rule::exists("$centralConnection.app_releases", 'id'),
            ],
        ]);

        $tenantId = $request->route('tenant');
        $releaseId = $request->input('release_id');

        $result = $this->selfUpdateService->applyUpdate($tenantId, $releaseId);

        if ($result['success']) {
            return redirect()
                ->route('tenant.updates.index', ['tenant' => $tenantId])
                ->with('success', 'Update applied successfully');
        }

        return back()
            ->with('error', 'Update failed: ' . $result['error']);
    }
}
