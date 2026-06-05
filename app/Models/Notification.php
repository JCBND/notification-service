<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $message
 * @property NotificationChannel $channel
 * @property NotificationStatus $status
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'message',
        'channel',
        'status',
        'attempts',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'channel'    => NotificationChannel::class,
        'status'     => NotificationStatus::class,
        'sent_at'    => 'datetime',
        'failed_at'  => 'datetime',
        'attempts'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status'  => NotificationStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status'         => NotificationStatus::Failed,
            'failed_at'      => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
