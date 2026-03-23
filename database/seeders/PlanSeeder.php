<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Basic', 'price' => 499, 'max_branches' => 1, 'max_users' => 5],
            ['name' => 'Standard', 'price' => 999, 'max_branches' => 3, 'max_users' => 15],
            ['name' => 'Premium', 'price' => 1999, 'max_branches' => 10, 'max_users' => 50],
        ];

        foreach ($plans as $plan) {
            Plan::query()->firstOrCreate(['name' => $plan['name']], $plan);
        }
    }
}