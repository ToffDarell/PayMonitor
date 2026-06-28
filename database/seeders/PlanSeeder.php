<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Plan::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $features = [
            'basic_members',
            'loan_management',
            'loan_types',
            'payment_tracking',
            'basic_reports',
            'branch_management',
            'multi_user',
            'collections_dashboard',
            'overdue_loan_management',
            'advanced_reports',
            'member_documents',
            'loan_documents',
            'custom_roles',
            'advanced_analytics',
            'audit_logs',
        ];

        Plan::query()->create([
            'name' => 'Professional Monthly',
            'billing_cycle' => 'monthly',
            'price' => 1999,
            'max_branches' => 9999,
            'max_users' => 9999,
            'description' => 'Complete lending management — billed monthly.',
            'features' => $features,
        ]);

        Plan::query()->create([
            'name' => 'Professional Yearly',
            'billing_cycle' => 'yearly',
            'price' => 19188,
            'max_branches' => 9999,
            'max_users' => 9999,
            'description' => 'Complete lending management — billed yearly. Save 20%!',
            'features' => $features,
        ]);
    }
}
