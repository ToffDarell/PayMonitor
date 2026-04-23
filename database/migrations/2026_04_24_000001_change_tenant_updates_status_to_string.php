<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change the enum column to string to support new statuses:
        // updating, rolled_back (in addition to existing update_available, updated, failed)
        Schema::table('tenant_updates', function (Blueprint $table) {
            $table->string('status', 30)->default('update_available')->change();
        });
    }

    public function down(): void
    {
        // Revert back to enum (remove rows with new statuses first)
        DB::table('tenant_updates')
            ->whereIn('status', ['updating', 'rolled_back'])
            ->update(['status' => 'update_available']);

        Schema::table('tenant_updates', function (Blueprint $table) {
            $table->enum('status', ['update_available', 'updated', 'failed'])->default('update_available')->change();
        });
    }
};
