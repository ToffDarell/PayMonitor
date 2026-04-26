<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SampleX;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SampleX>
 */
class SampleXFactory extends Factory
{
    protected $model = SampleX::class;

    public function definition(): array
    {
        return [
            'number' => (string) fake()->randomNumber(),
            'description' => fake()->sentence(),
        ];
    }
}
