<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Done       = 'done';
    case Failed     = 'failed';
}
