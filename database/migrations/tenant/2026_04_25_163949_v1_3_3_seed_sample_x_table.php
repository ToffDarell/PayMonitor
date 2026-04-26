<?php

declare(strict_types=1);

use Database\Seeders\SampleXSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sample_x')) {
            Schema::create('sample_x', function (Blueprint $table): void {
                $table->id();
                $table->string('number');
                $table->string('description');
                $table->timestamps();
            });
        }

        (new SampleXSeeder())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_x');
    }
};
