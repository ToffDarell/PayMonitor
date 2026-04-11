@php
    $tenantName = $tenant?->name ?? $supportRequest->tenant_name;
    $tenantDomain = $tenant?->domains()->value('domain');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tenant Support Request</title>
</head>
<body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #dbe4ee;border-radius:16px;overflow:hidden;">
        <div style="background:#0f766e;padding:20px 24px;color:#ffffff;">
            <h1 style="margin:0;font-size:22px;line-height:1.3;">Tenant Support Request</h1>
            <p style="margin:8px 0 0;font-size:14px;opacity:0.9;">A tenant submitted a new support concern from their portal.</p>
        </div>

        <div style="padding:24px;">
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="padding:8px 0;font-weight:700;width:180px;">Tenant</td>
                    <td style="padding:8px 0;">{{ $tenantName }}</td>
                </tr>
                @if($tenantDomain)
                    <tr>
                        <td style="padding:8px 0;font-weight:700;">Domain</td>
                        <td style="padding:8px 0;">{{ $tenantDomain }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:8px 0;font-weight:700;">Requester</td>
                    <td style="padding:8px 0;">{{ $supportRequest->requester_name }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;font-weight:700;">Email</td>
                    <td style="padding:8px 0;">{{ $supportRequest->requester_email }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;font-weight:700;">Category</td>
                    <td style="padding:8px 0;">{{ ucfirst($supportRequest->category) }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;font-weight:700;">Subject</td>
                    <td style="padding:8px 0;">{{ $supportRequest->subject }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;font-weight:700;">Submitted</td>
                    <td style="padding:8px 0;">{{ $supportRequest->created_at?->format('M d, Y h:i A') }}</td>
                </tr>
            </table>

            <div style="margin-top:24px;padding:18px;border:1px solid #dbe4ee;border-radius:12px;background:#f8fafc;">
                <p style="margin:0 0 10px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#475569;">Message</p>
                <p style="margin:0;font-size:15px;line-height:1.65;white-space:pre-line;">{{ $supportRequest->message }}</p>
            </div>
        </div>
    </div>
</body>
</html>
