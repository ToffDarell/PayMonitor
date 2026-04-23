<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\TenantApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public TenantApplication $application,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PayMonitor Application Payment — Action Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-link',
            with: [
                'application' => $this->application,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
