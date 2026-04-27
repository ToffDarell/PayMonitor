<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Console\Command;

class MarkOverdueLoans extends Command
{
    protected $signature = 'loans:mark-overdue';

    protected $description = 'Automatically mark loans as overdue when due date has passed';

    public function handle(AuditService $auditService): int
    {
        $totalMarked = 0;

        Tenant::query()
            ->with('plan')
            ->get()
            ->each(function (Tenant $tenant) use (&$totalMarked, $auditService): void {
                $tenant->run(function () use (&$totalMarked, $tenant, $auditService): void {
                    Loan::query()
                        ->where('status', 'active')
                        ->whereDate('due_date', '<', today())
                        ->get()
                        ->each(function (Loan $loan) use (&$totalMarked, $tenant, $auditService): void {
                            $oldValues = $loan->toArray();

                            $loan->forceFill([
                                'status' => 'overdue',
                            ])->save();

                            if ($tenant->supportsAuditLogs()) {
                                $auditService->log('marked_overdue', $loan, $oldValues, $loan->fresh()->toArray());
                            }

                            $totalMarked++;
                        });
                });
            });

        $this->info("Marked {$totalMarked} loan(s) as overdue.");

        return self::SUCCESS;
    }
}
