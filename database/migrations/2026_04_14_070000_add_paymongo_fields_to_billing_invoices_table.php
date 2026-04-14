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
        Schema::table('billing_invoices', function (Blueprint $table): void {
            $table->string('paymongo_link_id')->nullable()->after('paid_at');
            $table->string('paymongo_payment_id')->nullable()->after('paymongo_link_id');
            $table->string('payment_url')->nullable()->after('paymongo_payment_id');
            $table->string('payment_method')->nullable()->after('payment_url');
            $table->string('paid_via')->nullable()->after('payment_method');
            $table->index('paymongo_link_id');
            $table->index('paymongo_payment_id');
        });

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE billing_invoices MODIFY COLUMN status ENUM('unpaid', 'pending_verification', 'paid', 'overdue') NOT NULL DEFAULT 'unpaid'"
            );
        }
    }

    public function down(): void
    {
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('billing_invoices')
                ->where('status', 'pending_verification')
                ->update(['status' => 'unpaid']);

            DB::statement(
                "ALTER TABLE billing_invoices MODIFY COLUMN status ENUM('unpaid', 'paid', 'overdue') NOT NULL DEFAULT 'unpaid'"
            );
        }

        Schema::table('billing_invoices', function (Blueprint $table): void {
            $table->dropIndex(['paymongo_link_id']);
            $table->dropIndex(['paymongo_payment_id']);
            $table->dropColumn([
                'paymongo_link_id',
                'paymongo_payment_id',
                'payment_url',
                'payment_method',
                'paid_via',
            ]);
        });
    }
};
