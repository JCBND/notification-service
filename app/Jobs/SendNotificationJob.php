<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Notification;
use App\Notifications\ChannelRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before marking as failed.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retries: 30s, 60s, 120s.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * Job timeout in seconds.
     */
    public int $timeout = 30;

    public function __construct(
        private readonly Notification $notification
    ) {}

    public function handle(ChannelRouter $router): void
    {
        $this->notification->incrementAttempts();

        $handler = $router->resolve($this->notification->channel);

        $handler->send($this->notification);

        $this->notification->markAsSent();
    }

    public function failed(Throwable $exception): void
    {
        $this->notification->markAsFailed($exception->getMessage());
    }
}
