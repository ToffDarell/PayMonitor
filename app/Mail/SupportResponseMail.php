<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SupportRequest;
use App\Models\SupportResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportResponseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportRequest $supportRequest,
        public SupportResponse $supportResponse
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: '.$this->supportRequest->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-response',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
