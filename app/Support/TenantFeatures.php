<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\TenantSetting;
use Illuminate\Support\Facades\DB;

final class TenantFeatures
{
    /**
     * Define the minimum version required for each feature.
     */
    private const FEATURE_VERSIONS = [
        'basic_members' => 'v1.0.0',
        'loan_management' => 'v1.0.0',
        'loan_types' => 'v1.0.0',
        'payment_tracking' => 'v1.0.0',
        'basic_reports' => 'v1.0.0',
        'branch_management' => 'v1.0.0',
        'multi_user' => 'v1.0.0',
        'collections_dashboard' => 'v1.1.0',
        'advanced_reports' => 'v1.1.0',
        'audit_logs' => 'v1.1.0',
        'member_documents' => 'v1.2.0',
        'loan_documents' => 'v1.2.0',
        'custom_roles' => 'v1.2.0',
        'advanced_analytics' => 'v1.3.0',
    ];

    /**
     * Check if a specific version has a feature.
     */
    public static function hasFeature(string $version, string $feature): bool
    {
        $minVersion = self::FEATURE_VERSIONS[$feature] ?? null;

        if ($minVersion === null) {
            return false;
        }

        // Remove 'v' prefix for comparison if present
        $version = ltrim($version, 'v');
        $minVersion = ltrim($minVersion, 'v');

        return version_compare($version, $minVersion, '>=');
    }

    /**
     * Check if the current tenant has access to a feature,
     * based on BOTH their version and their subscription plan.
     */
    public static function tenantHasFeature(string $feature): bool
    {
        // 1. Check if the tenant's version supports it
        $tenantVersion = TenantSetting::get('current_version', 'v1.0.0');
        $versionHasIt = self::hasFeature($tenantVersion, $feature);

        if (! $versionHasIt) {
            return false;
        }

        // 2. Check if the tenant's plan includes it
        $tenant = tenant();
        
        if (! $tenant) {
            return false;
        }

        // Switch to central DB to get plan features
        $centralConnection = (string) config('tenancy.database.central_connection', config('database.default'));

        $planFeatures = DB::connection($centralConnection)
            ->table('plans')
            ->join('tenants', 'plans.id', '=', 'tenants.plan_id')
            ->where('tenants.id', $tenant->id)
            ->value('plans.features');
            
        $planFeatures = json_decode($planFeatures ?? '[]', true);
        
        if (! is_array($planFeatures)) {
            $planFeatures = [];
        }

        return in_array($feature, $planFeatures, true);
    }
}
