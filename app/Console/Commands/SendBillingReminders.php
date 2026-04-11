<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\TenantInvoiceMail;
use App\Models\BillingInvoice;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBillingReminders extends Command
{
    protected $signature = 'billing:send-reminders';

    protected $description = 'Send billing reminders to tenants with upcoming due dates';

    public function handle(): int
    {
        $sentCount = 0;

        $sentCount += $this->sendUpcomingReminders(7, 'due_soon', 'Subscription reminder sent for tenants due in 7 days.');
        $sentCount += $this->sendUpcomingReminders(3, 'urgent', 'Urgent reminder sent for tenants due in 3 days.');
        $sentCount += $this->sendOverdueReminders();

        $this->info("Billing reminders processed successfully. Emails dispatched: {$sentCount}.");

        return self::SUCCESS;
    }

    private function sendUpcomingReminders(int $days, string $variant, string $message): int
    {
        $tenants = Tenant::query()
            ->with('plan')
            ->where('status', 'active')
            ->whereDate('subscription_due_at', today()->copy()->addDays($days))
            ->get();

        foreach ($tenants as $tenant) {
            $invoice = BillingInvoice::firstOrCreateForTenantCycle(
                $tenant,
                $tenant->subscription_due_at ?? today()->copy()->addDays($days),
                "Auto-generated {$days}-day reminder invoice.",
            );

            Mail::to($tenant->email)->send(new TenantInvoiceMail($tenant, $invoice, $variant));
        }

        if ($tenants->isNotEmpty()) {
            $this->line($message);
        }

        return $tenants->count();
    }

    private function sendOverdueReminders(): int
    {
        $tenants = Tenant::query()
            ->with('plan')
            ->where('status', 'active')
            ->whereDate('subscription_due_at', '<', today())
            ->get();

        foreach ($tenants as $tenant) {
            $dueDate = $tenant->subscription_due_at ?? today()->subDay();
            $invoice = BillingInvoice::firstOrCreateForTenantCycle(
                $tenant,
                $dueDate,
                'Auto-generated overdue invoice.',
            );

            if ($invoice->status !== 'paid') {
                $invoice->forceFill(['status' => 'overdue'])->save();
            }

            $tenant->forceFill(['status' => 'overdue'])->save();

            Mail::to($tenant->email)->send(new TenantInvoiceMail(
                $tenant,
                $invoice,
                'overdue',
                $dueDate->diffInDays(today()),
            ));
        }

        if ($tenants->isNotEmpty()) {
            $this->line('Overdue reminders sent and tenant statuses updated.');
        }

        return $tenants->count();
    }
}
