<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\OverdueReminderMail;
use App\Models\Loan;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendOverdueReminders extends Command
{
    protected $signature = 'loans:send-overdue-reminders';

    protected $description = 'Send overdue reminder emails to members with overdue loans';

    public function handle(SmsService $smsService): int
    {
        $totalSent = 0;
        $totalSmsSent = 0;

        Tenant::query()
            ->with('plan')
            ->get()
            ->each(function (Tenant $tenant) use (&$totalSent, &$totalSmsSent, $smsService): void {
                $tenant->run(function () use (&$totalSent, &$totalSmsSent, $smsService): void {
                    $cutoff = Carbon::now()->subDays(7);

                    Loan::query()
                        ->with('member')
                        ->where('status', 'overdue')
                        ->where(function ($query) use ($cutoff): void {
                            $query->whereNull('last_reminder_sent_at')
                                ->orWhere('last_reminder_sent_at', '<', $cutoff);
                        })
                        ->chunk(100, function ($loans) use (&$totalSent, &$totalSmsSent, $smsService): void {
                            foreach ($loans as $loan) {
                                $member = $loan->member;

                                if ($member?->email !== null) {
                                    Mail::to($member->email)->send(new OverdueReminderMail($loan, $member));
                                    $totalSent++;
                                }

                                $daysOverdue = $loan->due_date ? $loan->due_date->diffInDays(today()) : 0;

                                if ($daysOverdue > 30 && filled($member?->phone)) {
                                    $smsService->sendToMember($member, $smsService->escalatedOverdue($loan, $member));
                                    $totalSmsSent++;
                                }

                                $loan->forceFill([
                                    'last_reminder_sent_at' => now(),
                                ])->save();
                            }
                        });
                });
            });

        $this->info("Sent {$totalSent} email reminder(s) and {$totalSmsSent} escalated SMS(es).");

        return self::SUCCESS;
    }
}
