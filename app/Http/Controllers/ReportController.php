<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\StoreReportRequest;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $service
    ) {}

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->service->requestReport(
            userId: (int) $request->validated('user_id'),
            from: Carbon::parse((string) $request->validated('period_from'))->startOfDay(),
            to: Carbon::parse((string) $request->validated('period_to'))->endOfDay(),
        );

        return response()->json([
            'data' => [
                'id'          => $report->id,
                'status'      => $report->status->value,
                'period_from' => $report->period_from->toDateString(),
                'period_to'   => $report->period_to->toDateString(),
                'created_at'  => $report->created_at,
            ],
        ], 202);
    }

    public function show(int $id): JsonResponse
    {
        $report = $this->service->find($id);

        if ($report === null) {
            return response()->json(['message' => 'Report not found.'], 404);
        }

        return response()->json([
            'data' => [
                'id'             => $report->id,
                'status'         => $report->status->value,
                'period_from'    => $report->period_from->toDateString(),
                'period_to'      => $report->period_to->toDateString(),
                'failure_reason' => $report->failure_reason,
                'created_at'     => $report->created_at,
                'updated_at'     => $report->updated_at,
            ],
        ]);
    }

    public function download(int $id): JsonResponse
    {
        $report = $this->service->find($id);

        if ($report === null) {
            return response()->json(['message' => 'Report not found.'], 404);
        }

        if ($report->status !== ReportStatus::Done || $report->file_path === null) {
            return response()->json(['message' => 'Report is not ready yet.'], 409);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($report->file_path)) {
            return response()->json(['message' => 'Report file not found on disk.'], 404);
        }

        $content = $disk->get($report->file_path);

        return response()->json([
            'data' => [
                'report_id' => $report->id,
                'content'   => $content,
            ],
        ]);
    }
}