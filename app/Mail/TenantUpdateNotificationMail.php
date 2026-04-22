<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantUpdateNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $adminName,
        public readonly string $latestVersion,
        public readonly string $releaseName,
        public readonly string $changelog,
        public readonly string $loginUrl,
        public readonly string $updatesUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Action Required: PayMonitor Update {$this->latestVersion} Available",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-update-notification',
        );
    }
}
