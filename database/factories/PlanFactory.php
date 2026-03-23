<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Standard', 'Premium']),
            'price' => fake()->randomElement([499, 999, 1999]),
            'max_branches' => fake()->numberBetween(1, 10),
            'max_users' => fake()->numberBetween(5, 50),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
