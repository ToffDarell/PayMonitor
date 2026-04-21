<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Response</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px; font-weight: 600;">PayMonitor Support</h1>
        <p style="color: rgba(255, 255, 255, 0.9); margin: 10px 0 0 0; font-size: 14px;">Response to Your Support Request</p>
    </div>

    <div style="background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;">
        <p style="margin: 0 0 20px 0; font-size: 16px;">Hello <strong>{{ $supportRequest->requester_name }}</strong>,</p>

        <p style="margin: 0 0 20px 0;">We've received your support request and wanted to provide you with an update.</p>

        <div style="background: white; border-left: 4px solid #10b981; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <p style="margin: 0 0 10px 0; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Your Original Request</p>
            <p style="margin: 0 0 5px 0; font-weight: 600; font-size: 16px; color: #111827;">{{ $supportRequest->subject }}</p>
            <p style="margin: 0; color: #6b7280; font-size: 14px;">{{ Str::limit($supportRequest->message, 150) }}</p>
        </div>

        <div style="background: #ecfdf5; border-left: 4px solid #059669; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <p style="margin: 0 0 10px 0; font-size: 12px; color: #065f46; text-transform: uppercase; font-weight: 600;">
                Response from {{ $supportResponse->responder_name }}
            </p>
            <p style="margin: 0; color: #111827; font-size: 15px; white-space: pre-wrap;">{{ $supportResponse->message }}</p>
        </div>

        <div style="background: white; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #e5e7eb;">
            <table style="width: 100%; font-size: 14px;">
                <tr>
                    <td style="padding: 5px 0; color: #6b7280;">Ticket ID:</td>
                    <td style="padding: 5px 0; text-align: right; font-weight: 600;">#{{ $supportRequest->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; color: #6b7280;">Status:</td>
                    <td style="padding: 5px 0; text-align: right;">
                        <span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                            {{ ucfirst(str_replace('_', ' ', $supportRequest->status)) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; color: #6b7280;">Category:</td>
                    <td style="padding: 5px 0; text-align: right; font-weight: 600;">{{ ucfirst($supportRequest->category) }}</td>
                </tr>
            </table>
        </div>

        <p style="margin: 20px 0;">If you have any additional questions or need further assistance, please reply to this email or submit a new support request from your dashboard.</p>

        <div style="text-align: center; margin: 30px 0 20px 0;">
            <a href="{{ $supportRequest->tenant_id ? 'http://'.$supportRequest->tenant_id.'.'.config('app.domain', 'paymonitor.test').'/settings?tab=support' : '#' }}" 
               style="display: inline-block; background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                View Support Dashboard
            </a>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

        <p style="margin: 0; font-size: 13px; color: #6b7280; text-align: center;">
            <strong>{{ config('app.name', 'PayMonitor') }}</strong><br>
            {{ config('app.support_email', 'support@paymonitor.test') }}<br>
            {{ config('app.support_phone', '+63 917 000 0000') }}
        </p>
    </div>
</body>
</html>
