<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SampleX;
use Illuminate\Database\Seeder;

class SampleXSeeder extends Seeder
{
    public function run(): void
    {
        if (SampleX::query()->count() === 0) {
            SampleX::factory(50)->create();
        }
    }
}
