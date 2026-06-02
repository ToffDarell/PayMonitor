<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\OverdueReminderMail;
use App\Models\Loan;
use App\Models\Tenant;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendOverdueReminders extends Command
{
    protected $signature = 'loans:send-overdue-reminders';

    protected $description = 'Send overdue reminder emails to members with overdue loans';

    public function handle(): int
    {
        $totalSent = 0;

        Tenant::query()
            ->with('plan')
            ->get()
            ->each(function (Tenant $tenant) use (&$totalSent): void {
                $tenant->run(function () use (&$totalSent): void {
                    $cutoff = Carbon::now()->subDays(7);

                    Loan::query()
                        ->with('member')
                        ->where('status', 'overdue')
                        ->where(function ($query) use ($cutoff): void {
                            $query->whereNull('last_reminder_sent_at')
                                ->orWhere('last_reminder_sent_at', '<', $cutoff);
                        })
                        ->chunk(100, function ($loans) use (&$totalSent): void {
                            foreach ($loans as $loan) {
                                $member = $loan->member;

                                if ($member?->email === null) {
                                    continue;
                                }

                                Mail::to($member->email)->send(new OverdueReminderMail($loan, $member));

                                $loan->forceFill([
                                    'last_reminder_sent_at' => now(),
                                ])->save();

                                $totalSent++;
                            }
                        });
                });
            });

        $this->info("Sent {$totalSent} overdue reminder(s).");

        return self::SUCCESS;
    }
}
