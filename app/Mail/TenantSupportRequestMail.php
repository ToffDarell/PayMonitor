<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSupportRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SupportRequest $supportRequest,
        public ?Tenant $tenant = null,
        public ?User $user = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tenant Support Request - '.$this->supportRequest->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-support-request',
        );
    }
}
