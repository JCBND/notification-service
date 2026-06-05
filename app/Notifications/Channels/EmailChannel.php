<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Contracts\NotificationChannelContract;
use App\Enums\NotificationChannel;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

final class EmailChannel implements NotificationChannelContract
{
    public function send(Notification $notification): bool
    {
        // Stub: real implementation would use Mailable / SES / SMTP
        Log::info('EmailChannel: sending notification', [
            'notification_id' => $notification->id,
            'user_id'         => $notification->user_id,
            'message'         => $notification->message,
        ]);

        // Simulate delivery
        return true;
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::Email;
    }
}
