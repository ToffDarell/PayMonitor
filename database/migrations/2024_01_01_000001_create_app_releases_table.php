<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('tag')->unique();
            $table->string('title');
            $table->text('changelog')->nullable();
            $table->string('release_url');
            $table->timestamp('published_at');
            $table->boolean('is_stable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamps();

            $table->index('published_at');
            $table->index(['is_stable', 'published_at']);
            $table->index('is_required');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
