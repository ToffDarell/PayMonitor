<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Loan;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverdueReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Loan $loan,
        public Member $member,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NOTICE: Overdue Loan Payment - '.$this->loan->loan_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.overdue-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
