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
            ->post(self::BASE_URL.'/links', [
                'data' => [
                    'attributes' => [
                        'amount' => $amount,
                        'description' => 'PayMonitor Subscription - '.$invoice->invoice_number,
                        'remarks' => $invoice->tenant->name ?? 'Tenant Subscription',
                    ],
                ],
            ])
            ->throw()
            ->json();

        return [
            'link_id' => Arr::get($response, 'data.id'),
            'checkout_url' => Arr::get($response, 'data.attributes.checkout_url'),
            'status' => Arr::get($response, 'data.attributes.status'),
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
            ->get(self::BASE_URL.'/links/'.$linkId)
            ->throw()
            ->json();

        $status = (string) Arr::get($response, 'data.attributes.status', 'unpaid');
        $payments = Arr::get($response, 'data.attributes.payments', []);

        return [
            'status' => $status,
            'is_paid' => $status === 'paid',
            'payment_id' => Arr::get($payments, '0.id'),
            'paid_at' => Arr::get($payments, '0.attributes.paid_at'),
            'method' => Arr::get($payments, '0.attributes.source.type'),
        ];
    }
}
