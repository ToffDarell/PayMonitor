<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (Module::query()->count() === 0) {
            Module::factory()
                ->count(50)
                ->sequencedPlanIds()
                ->create();
        }
    }
}
