<?php

declare(strict_types=1);

use Database\Seeders\ModuleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new ModuleSeeder())->run();
    }

    public function down(): void
    {
        \App\Models\Module::query()->delete();
    }
};
