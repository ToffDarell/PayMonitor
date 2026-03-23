<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->foreignId('plan_id')
                ->index()
                ->constrained('plans')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('address')->nullable();
            $table->string('admin_name')->nullable();
            $table->enum('status', ['active', 'overdue', 'suspended', 'inactive'])->default('active');
            $table->date('subscription_due_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
