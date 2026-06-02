<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->default(0)->after('amount_due');
            $table->decimal('balance', 15, 2)->default(0)->after('amount_paid');
        });

        DB::statement("ALTER TABLE loan_schedules MODIFY status ENUM('pending', 'partially_paid', 'paid', 'overdue') DEFAULT 'pending'");

        DB::statement('UPDATE loan_schedules SET amount_paid = amount_due, balance = 0 WHERE status = ?', ['paid']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE loan_schedules MODIFY status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending'");

        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'balance']);
        });
    }
};
