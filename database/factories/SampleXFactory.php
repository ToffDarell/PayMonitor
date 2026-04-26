<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SampleX;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SampleX>
 */
class SampleXFactory extends Factory
{
    protected $model = SampleX::class;

    public function definition(): array
    {
        $memberNumber = fake()->unique()->numberBetween(1, 999);
        $loanNumber = fake()->unique()->numberBetween(1, 999);
        $loanTypes = ['Salary Loan', 'Business Loan', 'Emergency Loan', 'Educational Loan'];
        $statuses = ['active', 'fully_paid', 'overdue', 'restructured'];
        $branchNames = ['North Branch', 'Central Branch', 'South Branch', 'West Branch'];

        return [
            'number' => sprintf('MBR-%03d / LN-%03d', $memberNumber, $loanNumber),
            'description' => sprintf(
                '%s, %s | %s | %s | PHP %s | %s',
                fake()->lastName(),
                fake()->firstName(),
                fake()->randomElement($branchNames),
                fake()->randomElement($loanTypes),
                number_format((float) fake()->numberBetween(5000, 75000), 2),
                strtoupper((string) fake()->randomElement($statuses))
            ),
        ];
    }
}
