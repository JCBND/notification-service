<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Http\Requests\ListNotificationsRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $service
    ) {}

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $notification = $this->service->create(
            userId: (int) $request->validated('user_id'),
            message: (string) $request->validated('message'),
            channel: NotificationChannel::from((string) $request->validated('channel')),
        );

        return response()->json([
            'data' => [
                'id'      => $notification->id,
                'status'  => $notification->status->value,
                'channel' => $notification->channel->value,
                'user_id' => $notification->user_id,
                'message' => $notification->message,
                'created_at' => $notification->created_at,
            ],
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $notification = $this->service->find($id);

        if ($notification === null) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return response()->json([
            'data' => [
                'id'             => $notification->id,
                'status'         => $notification->status->value,
                'channel'        => $notification->channel->value,
                'user_id'        => $notification->user_id,
                'message'        => $notification->message,
                'attempts'       => $notification->attempts,
                'sent_at'        => $notification->sent_at,
                'failed_at'      => $notification->failed_at,
                'failure_reason' => $notification->failure_reason,
                'created_at'     => $notification->created_at,
                'updated_at'     => $notification->updated_at,
            ],
        ]);
    }

    public function index(ListNotificationsRequest $request, int $userId): JsonResponse
    {
        $status  = $request->validated('status')
            ? NotificationStatus::from((string) $request->validated('status'))
            : null;

        $channel = $request->validated('channel')
            ? NotificationChannel::from((string) $request->validated('channel'))
            : null;

        $perPage = (int) ($request->validated('per_page') ?? 15);

        $paginator = $this->service->getForUser($userId, $status, $channel, $perPage);

        return response()->json($paginator);
    }
}
