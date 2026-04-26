<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SampleX;
use Illuminate\Database\Seeder;

class SampleXSeeder extends Seeder
{
    public function run(): void
    {
        if (SampleX::query()->count() === 0) {
            $branches = ['North Branch', 'Central Branch', 'South Branch', 'West Branch'];
            $loanTypes = ['Salary Loan', 'Business Loan', 'Emergency Loan', 'Educational Loan'];
            $statuses = ['ACTIVE', 'FULLY_PAID', 'OVERDUE', 'RESTRUCTURED'];
            $lastNames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Ramos', 'Navarro'];
            $firstNames = ['Ana', 'Mark', 'Liza', 'John', 'Paolo', 'Grace', 'Rica', 'Carlo', 'Mae', 'Joshua'];

            $records = collect(range(1, 50))
                ->map(function (int $index) use ($branches, $loanTypes, $statuses, $lastNames, $firstNames): array {
                    $memberNumber = sprintf('MBR-%03d', $index);
                    $loanNumber = sprintf('LN-%03d', $index);
                    $fullName = $lastNames[($index - 1) % count($lastNames)].', '.$firstNames[($index - 1) % count($firstNames)];
                    $branch = $branches[($index - 1) % count($branches)];
                    $loanType = $loanTypes[($index - 1) % count($loanTypes)];
                    $status = $statuses[($index - 1) % count($statuses)];
                    $principal = 5000 + ($index * 1250);

                    return [
                        'number' => "{$memberNumber} / {$loanNumber}",
                        'description' => sprintf(
                            '%s | %s | %s | PHP %s | %s',
                            $fullName,
                            $branch,
                            $loanType,
                            number_format((float) $principal, 2),
                            $status
                        ),
                    ];
                })
                ->all();

            SampleX::query()->insert($records);
        }
    }
}
