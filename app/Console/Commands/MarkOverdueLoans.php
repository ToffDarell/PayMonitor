<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\SmsService;
use Illuminate\Console\Command;

class MarkOverdueLoans extends Command
{
    protected $signature = 'loans:mark-overdue';

    protected $description = 'Automatically mark loans as overdue when due date has passed';

    public function handle(AuditService $auditService, SmsService $smsService): int
    {
        $totalMarked = 0;

        Tenant::query()
            ->with('plan')
            ->get()
            ->each(function (Tenant $tenant) use (&$totalMarked, $auditService, $smsService): void {
                $tenant->run(function () use (&$totalMarked, $tenant, $auditService, $smsService): void {
                    Loan::query()
                        ->with('member')
                        ->where('status', 'active')
                        ->whereDate('due_date', '<', today())
                        ->get()
                        ->each(function (Loan $loan) use (&$totalMarked, $tenant, $auditService, $smsService): void {
                            $oldValues = $loan->toArray();

                            $loan->forceFill([
                                'status' => 'overdue',
                            ])->save();

                            if ($tenant->supportsAuditLogs()) {
                                $auditService->log('marked_overdue', $loan, $oldValues, $loan->fresh()->toArray());
                            }

                            try {
                                $member = $loan->member;
                                if (filled($member?->phone)) {
                                    $smsService->sendToMember($member, $smsService->overdueReminder($loan, $member));
                                }
                            } catch (\Throwable) {
                                // SMS failure must never break loan processing
                            }

                            $totalMarked++;
                        });
                });
            });

        $this->info("Marked {$totalMarked} loan(s) as overdue.");

        return self::SUCCESS;
    }
}
