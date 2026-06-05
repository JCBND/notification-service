<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\NotificationChannelContract;
use App\Enums\NotificationChannel;
use App\Notifications\ChannelRouter;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\TelegramChannel;
use InvalidArgumentException;
use Tests\TestCase;

final class ChannelRouterTest extends TestCase
{
    public function test_resolves_email_channel(): void
    {
        $router = new ChannelRouter([
            new EmailChannel(),
            new TelegramChannel(),
        ]);

        $handler = $router->resolve(NotificationChannel::Email);

        $this->assertInstanceOf(EmailChannel::class, $handler);
    }

    public function test_resolves_telegram_channel(): void
    {
        $router = new ChannelRouter([
            new EmailChannel(),
            new TelegramChannel(),
        ]);

        $handler = $router->resolve(NotificationChannel::Telegram);

        $this->assertInstanceOf(TelegramChannel::class, $handler);
    }

    public function test_throws_for_unregistered_channel(): void
    {
        $router = new ChannelRouter([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No handler registered for channel: email');

        $router->resolve(NotificationChannel::Email);
    }

    public function test_all_channels_implement_contract(): void
    {
        $channels = [new EmailChannel(), new TelegramChannel()];

        foreach ($channels as $channel) {
            $this->assertInstanceOf(NotificationChannelContract::class, $channel);
        }
    }
}
