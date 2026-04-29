<?php

namespace App\Notifications;

use App\Models\SupportRequest;
use App\Models\SupportResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportRequest $supportRequest,
        public SupportResponse $supportResponse
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'support_response',
            'support_request_id' => $this->supportRequest->id,
            'subject' => $this->supportRequest->subject,
            'responder_name' => $this->supportResponse->responder_name,
            'message' => $this->supportResponse->message,
        ];
    }
}

