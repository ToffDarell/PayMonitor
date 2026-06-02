<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Employment & Financial Information
            $table->decimal('monthly_salary', 15, 2)->nullable()->after('occupation');

            // Co-maker Information (person who vouches for the member)
            $table->string('co_maker_name')->nullable()->after('monthly_salary');
            $table->string('co_maker_address')->nullable()->after('co_maker_name');
            $table->string('co_maker_contact_number')->nullable()->after('co_maker_address');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_salary',
                'co_maker_name',
                'co_maker_address',
                'co_maker_contact_number',
            ]);
        });
    }
};
