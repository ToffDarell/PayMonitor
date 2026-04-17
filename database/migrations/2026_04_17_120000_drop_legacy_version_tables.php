<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tenant_version_acknowledgements');
        Schema::dropIfExists('app_versions');
    }

    public function down(): void
    {
        Schema::create('app_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('version_number')->unique();
            $table->string('title');
            $table->text('changelog');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('released_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tenant_version_acknowledgements', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('version_id')
                ->constrained('app_versions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'version_id'], 'tenant_version_ack_unique');
        });
    }
};