<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();
        $id = Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999);

        return [
            'id' => $id,
            'plan_id' => Plan::factory(),
            'name' => $name,
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->address(),
            'admin_name' => fake()->name(),
            'status' => 'active',
            'subscription_due_at' => now()->addMonth()->toDateString(),
        ];
    }
}
