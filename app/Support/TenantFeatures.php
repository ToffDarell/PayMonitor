<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\TenantSetting;
use Illuminate\Support\Facades\DB;

final class TenantFeatures
{
    /**
     * @var array<string, bool>|null
     */
    private static ?array $tenantFeatureAvailability = null;

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
        'overdue_loan_management' => 'v1.3.9',
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
        return self::tenantFeatureAvailability()[$feature] ?? false;
    }

    /**
     * @param  array<int, string>  $features
     * @return array<string, bool>
     */
    public static function tenantFeatureMap(array $features): array
    {
        $availability = self::tenantFeatureAvailability();

        $featureMap = [];

        foreach ($features as $feature) {
            $featureMap[$feature] = $availability[$feature] ?? false;
        }

        return $featureMap;
    }

    /**
     * @return array<string, bool>
     */
    private static function tenantFeatureAvailability(): array
    {
        if (self::$tenantFeatureAvailability !== null) {
            return self::$tenantFeatureAvailability;
        }

        $tenantVersion = TenantSetting::get('current_version', 'v1.0.0');
        $tenant = tenant();

        if (! $tenant) {
            return self::$tenantFeatureAvailability = [];
        }

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

        $availability = [];

        foreach (self::FEATURE_VERSIONS as $feature => $minVersion) {
            $availability[$feature] = self::hasFeature($tenantVersion, $feature)
                && in_array($feature, $planFeatures, true);
        }

        return self::$tenantFeatureAvailability = $availability;
    }
}
