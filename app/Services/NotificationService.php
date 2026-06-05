<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class NotificationService
{
    public function create(int $userId, string $message, NotificationChannel $channel): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'message' => $message,
            'channel' => $channel,
            'status'  => NotificationStatus::Pending,
            'attempts' => 0,
        ]);

        SendNotificationJob::dispatch($notification);

        return $notification;
    }

    public function getForUser(
        int $userId,
        ?NotificationStatus $status = null,
        ?NotificationChannel $channel = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Notification::where('user_id', $userId)
            ->latest();

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($channel !== null) {
            $query->where('channel', $channel);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Notification
    {
        return Notification::find($id);
    }
}
