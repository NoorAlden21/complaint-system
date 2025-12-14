<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Repositories\Reports\ReportExportRepositoryInterface;
use App\Services\Reports\PerformanceReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePerformanceReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $reportId)
    {
    }

    public function handle(
        ReportExportRepositoryInterface $reports,
        PerformanceReportService $service
    ): void {
        $report = $reports->findById($this->reportId);

        if (!$report) {
            return;
        }

        $report->status = 'running';
        $report->started_at = now();
        $reports->save($report);

        try {
            $report = $service->generate($report);

            $report->status = 'success';
            $report->finished_at = now();
            $reports->save($report);
        } catch (\Throwable $e) {
            $report->status = 'failed';
            $report->finished_at = now();
            $report->error_message = $e->getMessage();
            $reports->save($report);

            throw $e;
        }
    }
}
