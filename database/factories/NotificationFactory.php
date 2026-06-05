<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
final class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'  => User::factory(),
            'message'  => $this->faker->sentence(),
            'channel'  => $this->faker->randomElement(NotificationChannel::cases()),
            'status'   => NotificationStatus::Pending,
            'attempts' => 0,
        ];
    }

    public function sent(): static
    {
        return $this->state([
            'status'  => NotificationStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function failed(string $reason = 'Timeout'): static
    {
        return $this->state([
            'status'         => NotificationStatus::Failed,
            'failed_at'      => now(),
            'failure_reason' => $reason,
        ]);
    }
}
