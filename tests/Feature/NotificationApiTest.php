<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/notifications', [
            'user_id' => $user->id,
            'message' => 'Hello world',
            'channel' => 'email',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'message' => 'Hello world',
            'channel' => 'email',
            'status'  => 'pending',
        ]);

        Queue::assertPushed(SendNotificationJob::class);
    }

    public function test_create_notification_dispatches_job(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/notifications', [
            'user_id' => $user->id,
            'message' => 'Test dispatch',
            'channel' => 'telegram',
        ]);

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) {
            return true; // Job was dispatched
        });
    }

    public function test_create_notification_validates_message_max_length(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/notifications', [
            'user_id' => $user->id,
            'message' => str_repeat('a', 501),
            'channel' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_create_notification_validates_invalid_channel(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/notifications', [
            'user_id' => $user->id,
            'message' => 'Test',
            'channel' => 'fax',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);
    }

    public function test_create_notification_validates_nonexistent_user(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications', [
            'user_id' => 99999,
            'message' => 'Test',
            'channel' => 'email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_can_get_notification_status(): void
    {
        $notification = Notification::factory()->sent()->create();

        $response = $this->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.status', 'sent');
    }

    public function test_returns_404_for_missing_notification(): void
    {
        $response = $this->getJson('/api/v1/notifications/99999');

        $response->assertStatus(404);
    }

    public function test_can_list_user_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->for($user)->create();

        $response = $this->getJson("/api/v1/users/{$user->id}/notifications");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_notifications_by_status(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->for($user)->sent()->create();
        Notification::factory()->count(1)->for($user)->failed()->create();

        $response = $this->getJson("/api/v1/users/{$user->id}/notifications?status=sent");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_notifications_by_channel(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->for($user)->create(['channel' => NotificationChannel::Email]);
        Notification::factory()->count(1)->for($user)->create(['channel' => NotificationChannel::Telegram]);

        $response = $this->getJson("/api/v1/users/{$user->id}/notifications?channel=email");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
