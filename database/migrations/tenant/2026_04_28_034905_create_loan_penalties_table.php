<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('penalty_type', ['fixed', 'percentage', 'daily'])->default('percentage');
            $table->decimal('penalty_rate', 5, 2);
            $table->decimal('penalty_amount', 10, 2);
            $table->string('reason')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_penalties');
    }
};
