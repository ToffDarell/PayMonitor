<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;

final class TenantFeatures
{
    /**
     * @var array<string, bool>|null
     */
    private static ?array $tenantFeatureAvailability = null;

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
        $allFeatureKeys = Plan::getAvailableFeatures();

        foreach ($allFeatureKeys as $feature => $config) {
            $availability[$feature] = in_array($feature, $planFeatures, true);
        }

        return self::$tenantFeatureAvailability = $availability;
    }
}
