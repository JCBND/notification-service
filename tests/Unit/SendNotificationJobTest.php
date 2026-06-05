<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Notifications\ChannelRouter;
use App\Contracts\NotificationChannelContract;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class SendNotificationJobTest extends TestCase
{
    public function test_marks_notification_as_sent_on_success(): void
    {
        $notification = Mockery::mock(Notification::class)->makePartial();
        $notification->shouldReceive('incrementAttempts')->once();
        $notification->shouldReceive('markAsSent')->once();
        $notification->channel = NotificationChannel::Email;

        $channelHandler = Mockery::mock(NotificationChannelContract::class);
        $channelHandler->shouldReceive('send')->with($notification)->andReturn(true);

        $router = Mockery::mock(ChannelRouter::class);
        $router->shouldReceive('resolve')
            ->with(NotificationChannel::Email)
            ->andReturn($channelHandler);

        $job = new SendNotificationJob($notification);
        $job->handle($router);
    }

    public function test_marks_notification_as_failed_on_exception(): void
    {
        $notification = Mockery::mock(Notification::class)->makePartial();
        $notification->shouldReceive('markAsFailed')
            ->once()
            ->with('Channel error');

        $job = new SendNotificationJob($notification);
        $job->failed(new RuntimeException('Channel error'));
    }

    public function test_job_has_correct_retry_config(): void
    {
        $notification = Mockery::mock(Notification::class)->makePartial();
        $job = new SendNotificationJob($notification);

        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 60, 120], $job->backoff);
    }
}
