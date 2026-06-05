<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Notification;

interface NotificationChannelContract
{
    /**
     * Send the notification through this channel.
     * Returns true on success, throws on failure.
     */
    public function send(Notification $notification): bool;

    /**
     * The channel identifier this handler supports.
     */
    public function channel(): \App\Enums\NotificationChannel;
}
