<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $email,
        public string $password,
        public string $loginUrl,
        public string $mode = 'approved',
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->mode === 'resent'
            ? 'Your PayMonitor Credentials Have Been Refreshed'
            : 'Application Approved - Your PayMonitor Account Is Ready';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-welcome',
            with: [
                'tenant' => $this->tenant,
                'email' => $this->email,
                'password' => $this->password,
                'loginUrl' => $this->loginUrl,
                'mode' => $this->mode,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
