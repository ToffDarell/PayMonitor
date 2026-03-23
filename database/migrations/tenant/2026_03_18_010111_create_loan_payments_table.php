<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')
                ->index()
                ->constrained('loans')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id')
                ->index()
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('period_covered')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
