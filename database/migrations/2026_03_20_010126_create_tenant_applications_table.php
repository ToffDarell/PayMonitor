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
        Schema::create('tenant_applications', function (Blueprint $table) {
            $table->id();
            $table->string('cooperative_name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('admin_name');
            $table->string('admin_email');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_applications');
    }
};
