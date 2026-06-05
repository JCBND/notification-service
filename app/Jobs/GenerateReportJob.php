<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\ReportStatus;
use App\Models\Notification;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 600];

    public int $timeout = 120;

    public function __construct(
        private readonly Report $report
    ) {}

    public function handle(): void
    {
        // Transition to "processing" atomically to detect mid-flight failures on retry
        $updated = Report::where('id', $this->report->id)
            ->whereIn('status', [ReportStatus::Pending->value, ReportStatus::Failed->value])
            ->update(['status' => ReportStatus::Processing]);

        // Another worker already picked it up
        if ($updated === 0) {
            return;
        }

        $stats = $this->gatherStats();
        $filePath = $this->writeFile($stats);

        $this->report->update([
            'status'    => ReportStatus::Done,
            'file_path' => $filePath,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        // Only mark failed when all retries exhausted
        $this->report->update([
            'status'         => ReportStatus::Failed,
            'failure_reason' => $exception->getMessage(),
        ]);
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function gatherStats(): array
    {
        $stats = [];

        foreach (NotificationChannel::cases() as $channel) {
            $base = Notification::where('user_id', $this->report->user_id)
                ->where('channel', $channel)
                ->whereBetween('created_at', [$this->report->period_from, $this->report->period_to]);

            $stats[$channel->value] = [
                'total'  => (clone $base)->count(),
                'sent'   => (clone $base)->where('status', NotificationStatus::Sent)->count(),
                'failed' => (clone $base)->where('status', NotificationStatus::Failed)->count(),
            ];
        }

        return $stats;
    }

    /**
     * @param array<string, array<string, int>> $stats
     */
    private function writeFile(array $stats): string
    {
        $lines = [
            "Notification Report",
            "User ID : {$this->report->user_id}",
            "Period  : {$this->report->period_from->toDateString()} — {$this->report->period_to->toDateString()}",
            "Generated: " . now()->toDateTimeString(),
            str_repeat('-', 40),
        ];

        foreach ($stats as $channel => $data) {
            $lines[] = strtoupper($channel);
            $lines[] = "  Total : {$data['total']}";
            $lines[] = "  Sent  : {$data['sent']}";
            $lines[] = "  Failed: {$data['failed']}";
        }

        $content = implode("\n", $lines) . "\n";
        $path = config('reports.path', 'reports') . "/report_{$this->report->id}.txt";

        Storage::put($path, $content);

        return $path;
    }
}
