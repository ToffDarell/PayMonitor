<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\BillingInvoice;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public BillingInvoice $invoice,
        public ?CarbonInterface $nextBillingDate = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Receipt - {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-receipt',
            with: [
                'tenant' => $this->tenant,
                'invoice' => $this->invoice,
                'nextBillingDate' => $this->nextBillingDate,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
