<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('penalty_total', 10, 2)->default(0)->after('amount_paid');
            $table->boolean('is_delinquent')->default(false)->after('status');
            $table->timestamp('delinquent_at')->nullable()->after('is_delinquent');
            $table->timestamp('written_off_at')->nullable()->after('delinquent_at');
            $table->timestamp('restructured_at')->nullable()->after('written_off_at');
            $table->timestamp('demand_letter_sent_at')->nullable()->after('restructured_at');
        });

        DB::statement("ALTER TABLE loans MODIFY status ENUM('active', 'fully_paid', 'overdue', 'restructured', 'written_off') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE loans MODIFY status ENUM('active', 'fully_paid', 'overdue', 'restructured') DEFAULT 'active'");

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'penalty_total',
                'is_delinquent',
                'delinquent_at',
                'written_off_at',
                'restructured_at',
                'demand_letter_sent_at',
            ]);
        });
    }
};
