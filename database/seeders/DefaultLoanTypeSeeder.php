<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LoanType;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DefaultLoanTypeSeeder extends Seeder
{
    private const DEFAULT_TYPES = [
        ['name' => 'Money Loan', 'interest_rate' => 5, 'interest_type' => 'flat', 'max_term_months' => 12, 'description' => 'Short-term cash assistance loan.'],
        ['name' => 'Appliance Loan', 'interest_rate' => 3, 'interest_type' => 'flat', 'max_term_months' => 24, 'description' => 'Loan product for appliance purchases.'],
        ['name' => 'Emergency Loan', 'interest_rate' => 2, 'interest_type' => 'flat', 'max_term_months' => 12, 'description' => 'Short-term emergency financial assistance.'],
        ['name' => 'Salary Loan', 'interest_rate' => 3, 'interest_type' => 'flat', 'max_term_months' => 12, 'description' => 'Short-term salary-backed loan facility.'],
        ['name' => 'Medical Loan', 'interest_rate' => 2, 'interest_type' => 'flat', 'max_term_months' => 12, 'description' => 'For medical and hospitalization expenses.'],
        ['name' => 'Educational Loan', 'interest_rate' => 2, 'interest_type' => 'flat', 'max_term_months' => 24, 'description' => 'For tuition and educational expenses.'],
        ['name' => 'Business Loan', 'interest_rate' => 5, 'interest_type' => 'diminishing', 'max_term_months' => 36, 'description' => 'Working capital loan for cooperative members.'],
        ['name' => 'Agricultural Loan', 'interest_rate' => 3, 'interest_type' => 'flat', 'max_term_months' => 12, 'description' => 'For farming and agricultural needs.'],
        ['name' => 'Housing Loan', 'interest_rate' => 6, 'interest_type' => 'diminishing', 'max_term_months' => 120, 'description' => 'For home improvement or construction.'],
        ['name' => 'Personal Loan', 'interest_rate' => 4, 'interest_type' => 'flat', 'max_term_months' => 24, 'description' => 'For personal use and expenses.'],
        ['name' => 'Special Loan', 'interest_rate' => 0, 'interest_type' => 'flat', 'max_term_months' => 6, 'description' => 'Special loan with custom terms.'],
    ];

    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->command->info("Seeding default loan types for: {$tenant->name}");

            $tenant->run(function (): void {
                foreach (self::DEFAULT_TYPES as $type) {
                    LoanType::query()->firstOrCreate(
                        ['name' => $type['name']],
                        $type,
                    );
                }
            });
        }

        $this->command->info('Default loan types seeded successfully for all tenants.');
    }
}
