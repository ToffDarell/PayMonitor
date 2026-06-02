<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $existingPlanIds = Module::query()
            ->pluck('plan_id')
            ->filter(static fn (mixed $planId): bool => is_numeric($planId))
            ->map(static fn (mixed $planId): int => (int) $planId)
            ->all();

        $missingPlanIds = collect(range(1, 50))
            ->reject(static fn (int $planId): bool => in_array($planId, $existingPlanIds, true))
            ->values();

        if ($missingPlanIds->isEmpty()) {
            return;
        }

        $timestamp = Carbon::now();

        Module::query()->insert(
            $missingPlanIds
                ->map(static function (int $planId) use ($timestamp): array {
                    return [
                        'name' => "Module plan {$planId} is ready for tenant access.",
                        'plan_id' => $planId,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                })
                ->all()
        );
    }
}
