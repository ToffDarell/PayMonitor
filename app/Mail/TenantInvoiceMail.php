<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\BillingInvoice;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantInvoiceMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public BillingInvoice $invoice,
        public string $variant = 'invoice',
        public ?int $daysOverdue = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->variant) {
                'due_soon' => 'PayMonitor Subscription Due in 7 Days',
                'urgent' => 'PayMonitor Subscription Due in 3 Days',
                'overdue' => 'URGENT: PayMonitor Subscription Overdue',
                default => "PayMonitor Billing Invoice - {$this->invoice->invoice_number}",
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-invoice',
            with: [
                'tenant' => $this->tenant,
                'invoice' => $this->invoice,
                'variant' => $this->variant,
                'daysOverdue' => $this->daysOverdue,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
