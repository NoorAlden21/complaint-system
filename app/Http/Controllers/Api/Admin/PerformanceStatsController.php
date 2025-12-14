<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PerformanceStatsRequest;
use App\Services\Reports\PerformanceStatsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PerformanceStatsController extends Controller
{
    public function __construct(
        protected PerformanceStatsService $statsService
    ) {
    }

    public function show(PerformanceStatsRequest $request): JsonResponse
    {
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

        $stats = $this->statsService->compute($filters);

        return response()->json(['data' => $stats]);
    }
}
