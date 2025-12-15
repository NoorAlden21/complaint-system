<?php

namespace App\Services\Reports;

use App\Models\ReportExport;
use App\Repositories\Reports\ReportExportRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;

class PerformanceReportService
{
    public function __construct(
        protected ReportExportRepositoryInterface $reportsRepo,
        protected PerformanceStatsService $statsService
    ) {
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $filters['type'] = 'performance';

        return $this->reportsRepo->paginate($filters, $perPage);
    }

    public function generate(ReportExport $report): ReportExport
    {
        $disk = 'reports';
        $baseDir = "performance/{$report->id}";
        Storage::disk($disk)->makeDirectory($baseDir);

        // Force EN for this report generation (stats + view)
        $oldLocale = app()->getLocale();
        app()->setLocale('en');

        try {
            $filters = $report->filters;
            $stats = $this->statsService->compute($filters);

            if ($report->format === 'pdf') {
                $fileName = 'performance_report_' . Str::slug($stats['filters']['from'] . '_' . $stats['filters']['to']) . '.pdf';
                $path = "{$baseDir}/{$fileName}";

                $html = view('reports.performance', ['stats' => $stats])->render();

                $mpdf = new Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 10,
                    'margin_bottom' => 10,
                ]);

                // LTR (default) + English HTML
                $mpdf->WriteHTML($html);

                Storage::disk($disk)->put($path, $mpdf->Output('', 'S'));

                $report->file_disk = $disk;
                $report->file_path = $path;
                $report->file_size = Storage::disk($disk)->size($path);
                $report->save();

                return $report;
            }

            // CSV
            $fileName = 'performance_report_' . Str::slug($stats['filters']['from'] . '_' . $stats['filters']['to']) . '.csv';
            $path = "{$baseDir}/{$fileName}";

            $tmp = tmpfile();
            $tmpPath = stream_get_meta_data($tmp)['uri'];
            $fh = fopen($tmpPath, 'w');

            fputcsv($fh, ['Performance Report']);
            fputcsv($fh, ['From', $stats['filters']['from']]);
            fputcsv($fh, ['To', $stats['filters']['to']]);
            fputcsv($fh, ['Generated At', $stats['generated_at']]);
            fputcsv($fh, []);

            fputcsv($fh, ['KPIs']);
            foreach ($stats['kpis'] as $k => $v) {
                fputcsv($fh, [$k, is_scalar($v) || $v === null ? $v : json_encode($v)]);
            }
            fputcsv($fh, []);

            fputcsv($fh, ['Breakdowns - By Status']);
            fputcsv($fh, ['key', 'label', 'count', 'percentage']);
            foreach ($stats['breakdowns']['by_status'] as $row) {
                fputcsv($fh, [$row['key'], $row['label'], $row['count'], $row['percentage']]);
            }
            fputcsv($fh, []);

            fputcsv($fh, ['Breakdowns - By Priority']);
            fputcsv($fh, ['key', 'label', 'count', 'percentage']);
            foreach ($stats['breakdowns']['by_priority'] as $row) {
                fputcsv($fh, [$row['key'], $row['label'], $row['count'], $row['percentage']]);
            }
            fputcsv($fh, []);

            fputcsv($fh, ['Breakdowns - By Department']);
            fputcsv($fh, ['id', 'name', 'count', 'percentage']);
            foreach ($stats['breakdowns']['by_department'] as $row) {
                fputcsv($fh, [$row['id'], $row['name'], $row['count'], $row['percentage']]);
            }
            fputcsv($fh, []);

            fputcsv($fh, ['Breakdowns - By Region']);
            fputcsv($fh, ['id', 'name', 'count', 'percentage']);
            foreach ($stats['breakdowns']['by_region'] as $row) {
                fputcsv($fh, [$row['id'], $row['name'], $row['count'], $row['percentage']]);
            }
            fputcsv($fh, []);

            fputcsv($fh, ['Breakdowns - By Category']);
            fputcsv($fh, ['id', 'label', 'count', 'percentage']);
            foreach ($stats['breakdowns']['by_category'] as $row) {
                fputcsv($fh, [$row['id'], $row['label'], $row['count'], $row['percentage']]);
            }
            fputcsv($fh, []);

            fputcsv($fh, ['Trends - Created Per Day']);
            fputcsv($fh, ['date', 'count']);
            foreach ($stats['trends']['created_per_day'] as $row) {
                fputcsv($fh, [$row['date'], $row['count']]);
            }
            fputcsv($fh, []);

            fputcsv($fh, ['Trends - Resolved Per Day']);
            fputcsv($fh, ['date', 'count']);
            foreach ($stats['trends']['resolved_per_day'] as $row) {
                fputcsv($fh, [$row['date'], $row['count']]);
            }

            fclose($fh);

            Storage::disk($disk)->put($path, file_get_contents($tmpPath));
            fclose($tmp);

            $report->file_disk = $disk;
            $report->file_path = $path;
            $report->file_size = Storage::disk($disk)->size($path);

            return $this->reportsRepo->save($report);
        } finally {
            app()->setLocale($oldLocale);
        }
    }
}
