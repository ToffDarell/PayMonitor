<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table): void {
            $table->decimal('payment_amount', 10, 2)->nullable()->after('plan_id');
            $table->string('payment_reference')->nullable()->after('payment_amount');
            $table->string('payment_proof_path')->nullable()->after('payment_reference');
            $table->enum('payment_status', ['pending', 'verified', 'rejected'])->default('pending')->after('payment_proof_path');
            $table->foreignId('payment_verified_by')->nullable()->after('payment_status')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable()->after('payment_verified_by');
        });

        DB::table('tenant_applications')
            ->select(['id', 'reviewed_by', 'reviewed_at'])
            ->where('status', 'approved')
            ->orderBy('id')
            ->chunkById(100, function ($applications): void {
                foreach ($applications as $application) {
                    DB::table('tenant_applications')
                        ->where('id', $application->id)
                        ->update([
                            'payment_status' => 'verified',
                            'payment_verified_by' => $application->reviewed_by,
                            'payment_verified_at' => $application->reviewed_at ?? now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_verified_by');
            $table->dropColumn([
                'payment_amount',
                'payment_reference',
                'payment_proof_path',
                'payment_status',
                'payment_verified_at',
            ]);
        });
    }
};
