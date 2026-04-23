<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BillingInvoice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoService
{
    private const BASE_URL = 'https://api.paymongo.com/v1';

    public function createPaymentLink(BillingInvoice $invoice): array
    {
        $secretKey = (string) config('paymongo.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('PayMongo secret key is not configured.');
        }

        $invoice->loadMissing('tenant');

        $amount = (int) round((float) $invoice->amount * 100);

        $response = Http::acceptJson()
            ->withBasicAuth($secretKey, '')
            ->post(self::BASE_URL.'/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'line_items' => [
                            [
                                'amount'      => $amount,
                                'currency'    => 'PHP',
                                'name'        => 'PayMonitor Subscription - '.$invoice->invoice_number,
                                'quantity'    => 1,
                            ],
                        ],
                        'payment_method_types' => ['card', 'gcash', 'paymaya', 'qrph'],
                        'description' => $invoice->tenant->name ?? 'Tenant Subscription',
                        'success_url' => route('billing.success', ['tenant' => $invoice->tenant_id, 'invoiceId' => $invoice->id], true),
                        'cancel_url' => route('billing.index', ['tenant' => $invoice->tenant_id], true),
                    ],
                ],
            ])
            ->throw()
            ->json();

        return [
            'link_id' => Arr::get($response, 'data.id'),
            'checkout_url' => Arr::get($response, 'data.attributes.checkout_url'),
        ];
    }

    public function verifyPayment(string $linkId): array
    {
        $secretKey = (string) config('paymongo.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('PayMongo secret key is not configured.');
        }

        $response = Http::acceptJson()
            ->withBasicAuth($secretKey, '')
            ->get(self::BASE_URL.'/checkout_sessions/'.$linkId)
            ->throw()
            ->json();

        $payments = Arr::get($response, 'data.attributes.payments', []);
        $payment  = $payments[0] ?? null;
        $status   = $payment ? Arr::get($payment, 'attributes.status') : 'unpaid';

        return [
            'status' => $status,
            'is_paid' => $status === 'paid',
            'payment_id' => Arr::get($payment, 'id'),
            'paid_at' => Arr::get($payment, 'attributes.paid_at'),
            'method' => Arr::get($payment, 'attributes.source.type'),
        ];
    }
}
