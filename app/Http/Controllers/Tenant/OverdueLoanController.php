<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\OverdueReminderMail;
use App\Models\Loan;
use App\Models\TenantSetting;
use App\Services\AuditService;
use App\Services\LoanService;
use App\Support\TenantPermissions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class OverdueLoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly AuditService $auditService,
    ) {}

    public function sendReminder(Request $request, Loan $loan): RedirectResponse
    {
        $this->authorizeOverdueManagement($request);
        $this->ensureLoanIsOverdue($loan);

        $loan->loadMissing('member');
        $member = $loan->member;
        $email = $member?->email;

        if (! filled($email)) {
            return back()->with('error', 'Member has no registered email address.');
        }

        Mail::to($email)->send(new OverdueReminderMail($loan, $member));

        $this->logAudit('reminder_sent', $loan, [], [
            'recipient_email' => $email,
            'sent_at' => now()->toDateTimeString(),
        ]);

        return back()->with('success', "Reminder sent to {$email}");
    }

    public function applyPenalty(Request $request, Loan $loan): RedirectResponse
    {
        $this->authorizeOverdueManagement($request);
        $this->ensureLoanIsOverdue($loan);

        $validated = $request->validate([
            'penalty_type' => ['required', 'in:fixed,percentage,daily'],
            'penalty_rate' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $loan = DB::transaction(function () use ($loan, $validated): Loan {
            $lockedLoan = Loan::query()->lockForUpdate()->findOrFail($loan->id);
            $rate = round((float) $validated['penalty_rate'], 2);
            $amount = $this->computePenaltyAmount($lockedLoan, $validated['penalty_type'], $rate);

            $lockedLoan->applyPenalty([
                'user_id' => auth()->id(),
                'penalty_type' => $validated['penalty_type'],
                'penalty_rate' => $rate,
                'penalty_amount' => $amount,
                'reason' => $validated['reason'] ?? null,
                'applied_at' => now(),
            ]);

            return $lockedLoan->fresh(['member', 'penalties.user']);
        });

        return back()->with('success', 'Penalty of P'.number_format((float) $loan->penalties->sortByDesc('id')->first()?->penalty_amount, 2).' applied to loan.');
    }

    public function markDelinquent(Request $request, Loan $loan): RedirectResponse
    {
        $this->authorizeOverdueManagement($request);
        $this->ensureLoanIsOverdue($loan);

        $oldValues = $loan->toArray();

        $loan->forceFill([
            'is_delinquent' => true,
            'delinquent_at' => $loan->delinquent_at ?? now(),
            'status' => 'overdue',
        ])->save();

        $this->logAudit('marked_delinquent', $loan, $oldValues, $loan->fresh()->toArray());

        return back()->with('success', 'Loan marked as delinquent.');
    }

    public function restructureLoan(Request $request, Loan $loan): RedirectResponse
    {
        $this->authorizeOverdueManagement($request);
        $this->ensureLoanIsOverdue($loan);

        $validated = $request->validate([
            'new_term_months' => ['required', 'integer', 'min:1', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($loan, $validated): void {
            $lockedLoan = Loan::query()
                ->with('loanSchedules')
                ->lockForUpdate()
                ->findOrFail($loan->id);

            $oldValues = $lockedLoan->toArray();
            $newTermMonths = (int) $validated['new_term_months'];
            $restructuredAmount = round((float) $lockedLoan->outstanding_balance, 2);
            $newMonthlyPayment = round($restructuredAmount / $newTermMonths, 2);
            $newDueDate = now()->addMonthsNoOverflow($newTermMonths);
            $newTotalPayable = round((float) $lockedLoan->amount_paid + $restructuredAmount, 2);

            $lockedLoan->loanSchedules()->whereNull('paid_at')->delete();

            $lockedLoan->forceFill([
                'term_months' => $newTermMonths,
                'total_interest' => 0,
                'total_payable' => $newTotalPayable,
                'monthly_payment' => $newMonthlyPayment,
                'due_date' => $newDueDate->toDateString(),
                'status' => 'restructured',
                'restructured_at' => now(),
                'notes' => $this->mergeNotes((string) ($lockedLoan->notes ?? ''), $validated['notes'] ?? null),
            ])->save();

            $this->loanService->generateAmortizationSchedule($lockedLoan->fresh());
            $this->logAudit('restructured', $lockedLoan, $oldValues, $lockedLoan->fresh()->toArray());
        });

        return back()->with('success', 'Loan restructured. New term: '.$validated['new_term_months'].' months.');
    }

    public function sendDemandLetter(Request $request, Loan $loan): Response
    {
        $this->authorizeOverdueManagement($request);
        $this->ensureLoanIsOverdue($loan);

        $loan->loadMissing(['member', 'user']);
        $oldValues = $loan->toArray();

        $loan->forceFill([
            'demand_letter_sent_at' => now(),
        ])->save();

        $settings = TenantSetting::allKeyed();
        $daysOverdue = $loan->due_date?->diffInDays(today()) ?? 0;
        $verificationCode = strtoupper(substr(hash('sha256', $loan->loan_number.'|'.now()->toDateString()), 0, 12));
        $logoDataUri = $this->resolveLogoDataUri($settings['logo_path'] ?? null);

        $this->logAudit('demand_letter_sent', $loan, $oldValues, $loan->fresh()->toArray());

        return Pdf::loadView('loans.demand-letter', [
            'loan' => $loan,
            'settings' => $settings,
            'daysOverdue' => $daysOverdue,
            'totalDue' => round((float) $loan->outstanding_balance, 2),
            'verificationCode' => $verificationCode,
            'logoDataUri' => $logoDataUri,
        ])->download('demand-letter-'.$loan->loan_number.'.pdf');
    }

    public function writeOff(Request $request, Loan $loan): RedirectResponse
    {
        $this->authorizeOverdueManagement($request);
        $this->ensureLoanIsOverdue($loan);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
            'confirmation' => ['required', 'in:CONFIRM'],
        ]);

        DB::transaction(function () use ($loan, $validated): void {
            $lockedLoan = Loan::query()->lockForUpdate()->findOrFail($loan->id);
            $oldValues = $lockedLoan->toArray();

            $lockedLoan->loanSchedules()->whereNull('paid_at')->delete();

            $lockedLoan->forceFill([
                'status' => 'written_off',
                'written_off_at' => now(),
                'outstanding_balance' => 0,
                'notes' => $this->mergeNotes((string) ($lockedLoan->notes ?? ''), '[Write-off Reason] '.$validated['reason']),
            ])->save();

            $this->logAudit('written_off', $lockedLoan, $oldValues, $lockedLoan->fresh()->toArray() + [
                'write_off_reason' => $validated['reason'],
            ]);
        });

        return back()->with('success', 'Loan written off. Balance cleared.');
    }

    private function authorizeOverdueManagement(Request $request): void
    {
        abort_unless(
            $request->user()?->hasTenantPermission(TenantPermissions::LOANS_UPDATE, ['tenant_admin', 'branch_manager', 'loan_officer']),
            403,
            'This action is unauthorized.',
        );
    }

    private function ensureLoanIsOverdue(Loan $loan): void
    {
        if ($loan->status === 'written_off' || $loan->status === 'fully_paid') {
            throw ValidationException::withMessages([
                'loan' => 'This loan can no longer be managed as overdue.',
            ]);
        }

        if (! $loan->isOverdue() && $loan->status !== 'overdue' && ! $loan->is_delinquent) {
            throw ValidationException::withMessages([
                'loan' => 'Loan is not overdue.',
            ]);
        }
    }

    private function computePenaltyAmount(Loan $loan, string $type, float $rate): float
    {
        return match ($type) {
            'fixed' => round($rate, 2),
            'percentage' => round((float) $loan->outstanding_balance * ($rate / 100), 2),
            'daily' => round($rate * max(1, $loan->due_date?->diffInDays(today()) ?? 0), 2),
        };
    }

    private function logAudit(string $action, Loan $loan, array $oldValues, array $newValues): void
    {
        if (! tenant()?->supportsAuditLogs()) {
            return;
        }

        $this->auditService->log($action, $loan, $oldValues, $newValues);
    }

    private function mergeNotes(string $existingNotes, ?string $newNote): string
    {
        $newNote = trim((string) $newNote);

        if ($newNote === '') {
            return $existingNotes;
        }

        return trim($existingNotes === '' ? $newNote : $existingNotes.PHP_EOL.$newNote);
    }

    private function resolveLogoDataUri(?string $logoPath): ?string
    {
        if (! filled($logoPath)) {
            return null;
        }

        if (! Storage::disk('public')->exists((string) $logoPath)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path((string) $logoPath);
        $mimeType = mime_content_type($fullPath);
        $contents = file_get_contents($fullPath);

        if ($mimeType === false || $contents === false) {
            return null;
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
