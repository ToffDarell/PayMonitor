<x-mail::message>
# Hello {{ $application->admin_name }},

Your PayMonitor application for **{{ $application->cooperative_name }}** has been reviewed and is awaiting payment to proceed.

To complete your application, please pay the subscription fee below:

| | |
|---|---|
| **Plan** | {{ $application->plan->name ?? 'N/A' }} |
| **Amount** | ₱{{ number_format((float) ($application->amount_paid ?? $application->plan?->price ?? 0), 2) }}/month |

<x-mail::button :url="$application->payment_url" color="success">
Pay Now
</x-mail::button>

After your payment is confirmed, your cooperative account will be set up within **24 hours** and login credentials will be sent to this email address.

If you have already completed payment, please disregard this message. Your application is being processed.

---

Need help? Contact us at [{{ config('app.support_email', 'support@paymonitor.com') }}](mailto:{{ config('app.support_email', 'support@paymonitor.com') }})

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
