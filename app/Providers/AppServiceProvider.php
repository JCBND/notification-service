<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\NotificationChannelContract;
use App\Notifications\ChannelRouter;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register all channel handlers (tagged for easy extension)
        $this->app->tag([
            EmailChannel::class,
            TelegramChannel::class,
        ], NotificationChannelContract::class);

        // Bind ChannelRouter — inject all tagged channels automatically
        $this->app->singleton(ChannelRouter::class, function () {
            /** @var iterable<NotificationChannelContract> $channels */
            $channels = $this->app->tagged(NotificationChannelContract::class);
            return new ChannelRouter($channels);
        });
    }

    public function boot(): void {}
}
