<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Tenant;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendOverdueSms extends Command
{
    protected $signature = 'loans:send-overdue-sms';

    protected $description = 'Send overdue SMS reminders to members with overdue loans';

    public function handle(SmsService $smsService): int
    {
        $totalSent = 0;

        Tenant::query()
            ->with('plan')
            ->get()
            ->each(function (Tenant $tenant) use (&$totalSent, $smsService): void {
                $tenant->run(function () use (&$totalSent, $smsService): void {
                    $cutoff = Carbon::now()->subDays(7);

                    Loan::query()
                        ->with('member')
                        ->where('status', 'overdue')
                        ->where(function ($query) use ($cutoff): void {
                            $query->whereNull('last_reminder_sent_at')
                                ->orWhere('last_reminder_sent_at', '<', $cutoff);
                        })
                        ->chunk(100, function ($loans) use (&$totalSent, $smsService): void {
                            foreach ($loans as $loan) {
                                $member = $loan->member;

                                if (blank($member?->phone)) {
                                    continue;
                                }

                                $message = $smsService->overdueReminder($loan, $member);
                                $sent = $smsService->sendToMember($member, $message);

                                if ($sent) {
                                    $loan->forceFill([
                                        'last_reminder_sent_at' => now(),
                                    ])->save();

                                    $totalSent++;
                                }
                            }
                        });
                });
            });

        $this->info("Sent {$totalSent} overdue SMS reminder(s).");

        return self::SUCCESS;
    }
}
