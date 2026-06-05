<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
    }

    public function test_creates_notification_with_pending_status(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $notification = $this->service->create(
            userId: $user->id,
            message: 'Test message',
            channel: NotificationChannel::Email,
        );

        $this->assertSame(NotificationStatus::Pending, $notification->status);
        $this->assertSame($user->id, $notification->user_id);
        $this->assertSame('Test message', $notification->message);
        $this->assertSame(NotificationChannel::Email, $notification->channel);
    }

    public function test_dispatches_send_job_on_create(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->service->create($user->id, 'msg', NotificationChannel::Telegram);

        Queue::assertPushed(SendNotificationJob::class);
    }

    public function test_returns_paginated_user_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->for($user)->create();

        $result = $this->service->getForUser($user->id, perPage: 3);

        $this->assertSame(3, $result->perPage());
        $this->assertSame(5, $result->total());
    }

    public function test_filters_by_status(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->for($user)->sent()->create();
        Notification::factory()->count(3)->for($user)->failed()->create();

        $result = $this->service->getForUser($user->id, status: NotificationStatus::Sent);

        $this->assertSame(2, $result->total());
    }

    public function test_find_returns_null_for_missing_id(): void
    {
        $result = $this->service->find(99999);

        $this->assertNull($result);
    }
}
