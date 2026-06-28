<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Member;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const int CREDIT_WARN_THRESHOLD = 5;

    private ?string $lastError = null;

    public function send(string $phone, string $message): bool
    {
        if (blank($phone)) {
            $this->lastError = 'Phone number is empty.';

            return false;
        }

        $phone = $this->normalizePhone($phone);

        $credits = $this->checkCredits();

        if ($credits === 0) {
            $this->lastError = 'No SMS credits remaining. Please top up your account.';

            Log::warning('SMS send skipped — no credits', [
                'phone' => substr_replace($phone, '****', 4, 4),
            ]);

            return false;
        }

        if ($credits !== null && $credits < self::CREDIT_WARN_THRESHOLD) {
            Log::warning("SMS credits running low ({$credits} remaining)", [
                'phone' => substr_replace($phone, '****', 4, 4),
            ]);
        }

        $response = Http::withBasicAuth(config('services.unisms.secret'), '')
            ->post('https://unismsapi.com/api/sms', [
                'recipient' => $phone,
                'content' => $message,
            ]);

        if ($response->failed()) {
            $this->lastError = 'API responded with status ' . $response->status();

            Log::warning('SMS failed to send', [
                'phone' => substr_replace($phone, '****', 4, 4),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $this->lastError = null;

        return true;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function checkCredits(): ?int
    {
        try {
            $response = Http::withBasicAuth(config('services.unisms.secret'), '')
                ->get('https://unismsapi.com/api/account');

            if ($response->failed()) {
                Log::warning('Failed to check SMS credits', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            return (int) ($response->json('sms_credits') ?? 0);
        } catch (\Throwable $e) {
            Log::warning('Exception while checking SMS credits', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function hasEnoughCredits(int $needed = 1): bool
    {
        $credits = $this->checkCredits();

        if ($credits === null) {
            return true;
        }

        return $credits >= $needed;
    }

    public function sendToMember(Member $member, string $message): bool
    {
        if (! $member->phone) {
            return false;
        }

        return $this->send($member->phone, $message);
    }

    public function overdueReminder(Loan $loan, Member $member): string
    {
        $daysOverdue = $loan->due_date ? $loan->due_date->diffInDays(today()) : 0;
        $coop = tenant()?->name ?? 'Cooperative';
        $amount = number_format((float) $loan->outstanding_balance, 2);

        return "Dear {$member->first_name}, your loan {$loan->loan_number} with {$coop} is {$daysOverdue} day(s) overdue. Please settle PHP {$amount} to avoid penalties. Thank you.";
    }

    public function escalatedOverdue(Loan $loan, Member $member): string
    {
        $coop = tenant()?->name ?? 'Cooperative';
        $contact = TenantSetting::get('contact_number', '');
        $amount = number_format((float) $loan->outstanding_balance, 2);

        $msg = "URGENT: {$member->first_name}, your loan {$loan->loan_number} with {$coop} is PHP {$amount} overdue. Settle immediately to avoid escalation.";

        if (filled($contact)) {
            $msg .= " Call {$contact}.";
        }

        return $msg;
    }

    public function paymentConfirmation(Loan $loan, float $amount, Member $member): string
    {
        $coop = tenant()?->name ?? 'Cooperative';
        $balance = number_format((float) $loan->outstanding_balance, 2);
        $paid = number_format($amount, 2);

        return "Confirmed: Payment of PHP {$paid} for loan {$loan->loan_number} received. Remaining balance: PHP {$balance}. Thank you, {$coop}.";
    }

    public function upcomingDue(Loan $loan, Member $member): string
    {
        $coop = tenant()?->name ?? 'Cooperative';
        $amount = number_format((float) $loan->monthly_payment, 2);
        $dueDate = $loan->due_date ? formatDate($loan->due_date) : 'N/A';

        return "Reminder: Your loan {$loan->loan_number} payment of PHP {$amount} is due on {$dueDate}. Pay on time to avoid penalties. - {$coop}";
    }

    public function demandLetterAlert(Loan $loan, Member $member): string
    {
        $coop = tenant()?->name ?? 'Cooperative';
        $amount = number_format((float) $loan->outstanding_balance, 2);

        return "Notice: A demand letter has been issued for your loan {$loan->loan_number} with {$coop}. Please settle PHP {$amount} within 7 days to avoid legal action.";
    }

    public function loanDisbursed(Loan $loan, Member $member): string
    {
        $coop = tenant()?->name ?? 'Cooperative';
        $amount = number_format((float) $loan->principal_amount, 2);
        $dueDate = $loan->due_date ? formatDate($loan->due_date) : 'N/A';

        return "Congratulations {$member->first_name}! Your loan of PHP {$amount} with {$coop} has been released. First payment due: {$dueDate}. Thank you.";
    }

    public function restructureConfirmed(Loan $loan, Member $member): string
    {
        $coop = tenant()?->name ?? 'Cooperative';
        $amount = number_format((float) $loan->monthly_payment, 2);

        return "Your loan {$loan->loan_number} with {$coop} has been restructured. New monthly payment: PHP {$amount} over {$loan->term_months} month(s). - {$coop}";
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '+63' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return '+63' . substr($digits, 1);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '639')) {
            return '+' . $digits;
        }

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        return '+63' . $digits;
    }
}
