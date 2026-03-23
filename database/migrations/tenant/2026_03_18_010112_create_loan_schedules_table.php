<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')
                ->index()
                ->constrained('loans')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->integer('period_number');
            $table->date('due_date');
            $table->decimal('amount_due', 10, 2);
            $table->decimal('principal_portion', 10, 2);
            $table->decimal('interest_portion', 10, 2);
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
