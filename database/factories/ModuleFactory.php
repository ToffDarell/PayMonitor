<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        $subjects = ['Billing', 'Member', 'Loan', 'Report', 'Collection', 'Branch', 'User', 'Audit'];
        $verbs = ['handles', 'tracks', 'manages', 'supports', 'secures', 'organizes', 'updates', 'verifies'];
        $objects = ['records', 'transactions', 'profiles', 'workflows', 'requests', 'statements', 'operations', 'schedules'];

        return [
            'name' => Str::of(sprintf(
                '%s module %s %s.',
                $subjects[array_rand($subjects)],
                $verbs[array_rand($verbs)],
                $objects[array_rand($objects)]
            ))->ucfirst()->value(),
            'plan_id' => 1,
        ];
    }

    public function sequencedPlanIds(): static
    {
        return $this->state(new Sequence(
            ...collect(range(1, 50))
                ->map(static fn (int $planId): array => ['plan_id' => $planId])
                ->all()
        ));
    }
}
