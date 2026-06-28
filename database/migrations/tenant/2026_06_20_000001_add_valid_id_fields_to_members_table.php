<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('valid_id_type', 100)->nullable()->after('civil_status');
            $table->string('valid_id_number', 50)->nullable()->after('valid_id_type');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn(['valid_id_type', 'valid_id_number']);
        });
    }
};
