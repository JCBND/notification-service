<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\NotificationChannelContract;
use App\Enums\NotificationChannel;
use InvalidArgumentException;

final class ChannelRouter
{
    /** @var array<string, NotificationChannelContract> */
    private array $channels = [];

    /**
     * @param iterable<NotificationChannelContract> $channels
     */
    public function __construct(iterable $channels)
    {
        foreach ($channels as $channel) {
            $this->channels[$channel->channel()->value] = $channel;
        }
    }

    public function resolve(NotificationChannel $channel): NotificationChannelContract
    {
        if (! isset($this->channels[$channel->value])) {
            throw new InvalidArgumentException(
                "No handler registered for channel: {$channel->value}"
            );
        }

        return $this->channels[$channel->value];
    }
}
