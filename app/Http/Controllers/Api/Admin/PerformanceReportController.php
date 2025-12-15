<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreatePerformanceReportRequest;
use App\Http\Resources\ReportExportResource;
use App\Jobs\GeneratePerformanceReportJob;
use App\Repositories\Reports\ReportExportRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PerformanceReportController extends Controller
{
    public function __construct(
        protected ReportExportRepositoryInterface $reports
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['type', 'status']);
        $perPage = (int) $request->query('per_page', 15);

        $reports = $this->reports->paginate($filters, $perPage);

        return ReportExportResource::collection($reports)->response();
    }

    public function store(CreatePerformanceReportRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $from = !empty($data['from']) ? Carbon::createFromFormat('Y-m-d', $data['from'])->startOfDay() : now()->startOfDay();
        $to   = !empty($data['to'])   ? Carbon::createFromFormat('Y-m-d', $data['to'])->endOfDay()     : now()->endOfDay();

        $filters = [
            'from_at' => $from,
            'to_at'   => $to,
            'department_id' => $data['department_id'] ?? null,
            'region_id'     => $data['region_id'] ?? null,
            'category_id'   => $data['category_id'] ?? null,
        ];

        $report = $this->reports->create([
            'type' => 'performance',
            'format' => $data['format'],
            'filters' => $filters,
            'requested_by' => $user->id,
            'status' => 'queued',
            'queued_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        GeneratePerformanceReportJob::dispatch($report->id);

        return response()->json([
            'message' => __('reports.queued'),
            'data' => new ReportExportResource($report),
        ], 202);
    }

    public function show(int $id)
    {
        $report = $this->reports->findById($id);

        if (!$report) {
            return response()->json(['message' => __('reports.not_found')], 404);
        }

        return (new ReportExportResource($report))->response();
    }

    public function download(int $id)
    {
        $report = $this->reports->findById($id);

        if (!$report) {
            return response()->json(['message' => __('reports.not_found')], 404);
        }

        if ($report->status !== 'success' || !$report->file_disk || !$report->file_path) {
            return response()->json(['message' => __('reports.not_ready')], 400);
        }

        if (!Storage::disk($report->file_disk)->exists($report->file_path)) {
            return response()->json(['message' => __('reports.file_missing')], 404);
        }

        return Storage::disk($report->file_disk)->download($report->file_path);
    }
}
