<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_updates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('app_release_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['update_available', 'updated', 'failed'])->default('update_available');
            $table->boolean('is_current')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('required_at')->nullable();
            $table->timestamp('grace_until')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'app_release_id']);
            $table->index(['tenant_id', 'is_current']);
            $table->index(['tenant_id', 'status']);
            $table->index(['required_at', 'grace_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_updates');
    }
};
