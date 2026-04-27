<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantSelfUpdateService;
use App\Services\TenantUpdateService;
use Illuminate\Http\JsonResponse;
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
        $tenantId = (string) (tenant()?->id ?? $request->route('tenant'));

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

        $tenantId = (string) (tenant()?->id ?? $request->route('tenant'));
        $releaseId = (int) $request->input('release_id');

        $result = $this->selfUpdateService->applyUpdate($tenantId, $releaseId);

        if ($result['success']) {
            $details = $result['details'] ?? [];
            $version = $result['release']?->tag ?? 'v1.0.0';
            $migrations = $details['migrations_run'] ?? 0;

            $message = "Successfully updated to {$version}.";
            if ($migrations > 0) {
                $message .= " {$migrations} migration(s) applied.";
            }

            return redirect()
                ->route('tenant.updates.index', ['tenant' => $tenantId])
                ->with('success', $message);
        }

        return back()
            ->with('error', 'Update failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Check if the tenant is currently in maintenance mode (JSON endpoint for polling).
     */
    public function pollStatus(Request $request): JsonResponse
    {
        $tenantId = (string) (tenant()?->id ?? $request->route('tenant'));

        $isInMaintenance = $this->selfUpdateService->isInMaintenanceMode($tenantId);
        $maintenanceInfo = $this->selfUpdateService->getMaintenanceInfo($tenantId);

        return response()->json([
            'in_maintenance' => $isInMaintenance,
            'info' => $maintenanceInfo,
        ]);
    }
}
