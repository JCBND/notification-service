<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'В обработке',
            self::Sent    => 'Отправлено',
            self::Failed  => 'Ошибка',
        };
    }
}
