<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Tenant;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendUpcomingReminders extends Command
{
    protected $signature = 'loans:send-upcoming-reminders';

    protected $description = 'Send SMS reminders for loan payments due within 3 days';

    public function handle(SmsService $smsService): int
    {
        $totalSent = 0;

        Tenant::query()
            ->with('plan')
            ->get()
            ->each(function (Tenant $tenant) use (&$totalSent, $smsService): void {
                $tenant->run(function () use (&$totalSent, $smsService): void {
                    $start = Carbon::today();
                    $end = Carbon::today()->addDays(3);

                    Loan::query()
                        ->with('member')
                        ->whereIn('status', ['active', 'overdue', 'restructured'])
                        ->whereBetween('due_date', [$start, $end])
                        ->chunk(100, function ($loans) use (&$totalSent, $smsService): void {
                            foreach ($loans as $loan) {
                                $member = $loan->member;

                                if (blank($member?->phone)) {
                                    continue;
                                }

                                $sent = $smsService->sendToMember($member, $smsService->upcomingDue($loan, $member));

                                if ($sent) {
                                    $totalSent++;
                                }
                            }
                        });
                });
            });

        $this->info("Sent {$totalSent} upcoming payment SMS reminder(s).");

        return self::SUCCESS;
    }
}
