<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_request_report_generation(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/reports', [
            'user_id'     => $user->id,
            'period_from' => '2024-01-01',
            'period_to'   => '2024-01-31',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.status', 'pending');

        Queue::assertPushed(GenerateReportJob::class);
    }

    public function test_can_get_report_status(): void
    {
        $report = Report::factory()->create([
            'status'      => ReportStatus::Processing,
            'period_from' => now()->subMonth(),
            'period_to'   => now(),
        ]);

        $response = $this->getJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_download_returns_409_when_not_ready(): void
    {
        $report = Report::factory()->create([
            'status'      => ReportStatus::Pending,
            'period_from' => now()->subMonth(),
            'period_to'   => now(),
        ]);

        $response = $this->getJson("/api/v1/reports/{$report->id}/download");

        $response->assertStatus(409);
    }

    public function test_can_download_ready_report(): void
    {
        Storage::fake();
        $disk = Storage::disk('local');
        $disk->put('reports/report_1.txt', 'Report content');

        $report = Report::factory()->create([
            'status'      => ReportStatus::Done,
            'file_path'   => 'reports/report_1.txt',
            'period_from' => now()->subMonth(),
            'period_to'   => now(),
        ]);

        $response = $this->getJson("/api/v1/reports/{$report->id}/download");

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition');
    }

    public function test_report_validates_period_dates(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/reports', [
            'user_id'     => $user->id,
            'period_from' => '2024-02-01',
            'period_to'   => '2024-01-01', // before period_from
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period_from']);
    }
}
