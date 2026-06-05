<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Contracts\NotificationChannelContract;
use App\Enums\NotificationChannel;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

final class TelegramChannel implements NotificationChannelContract
{
    public function send(Notification $notification): bool
    {
        // Stub: real implementation would call Telegram Bot API
        Log::info('TelegramChannel: sending notification', [
            'notification_id' => $notification->id,
            'user_id'         => $notification->user_id,
            'message'         => $notification->message,
        ]);

        // Simulate delivery
        return true;
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::Telegram;
    }
}
