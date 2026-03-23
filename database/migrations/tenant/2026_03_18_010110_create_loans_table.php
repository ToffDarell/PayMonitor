<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')
                ->index()
                ->constrained('members')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('branch_id')
                ->index()
                ->constrained('branches')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id')
                ->index()
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('loan_type_id')
                ->index()
                ->constrained('loan_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->string('loan_number')->unique();
            $table->decimal('principal_amount', 10, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->enum('interest_type', ['flat', 'diminishing']);
            $table->integer('term_months');
            $table->decimal('total_interest', 10, 2);
            $table->decimal('total_payable', 10, 2);
            $table->decimal('monthly_payment', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('outstanding_balance', 10, 2);
            $table->enum('status', ['active', 'fully_paid', 'overdue', 'restructured'])->default('active');
            $table->date('release_date')->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
