<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReportStatus;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use Carbon\Carbon;

final class ReportService
{
    public function requestReport(int $userId, Carbon $from, Carbon $to): Report
    {
        $report = Report::create([
            'user_id'     => $userId,
            'status'      => ReportStatus::Pending,
            'period_from' => $from,
            'period_to'   => $to,
        ]);

        GenerateReportJob::dispatch($report);

        return $report;
    }

    public function find(int $id): ?Report
    {
        return Report::find($id);
    }
}
