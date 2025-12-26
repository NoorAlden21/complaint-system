<?php

namespace App\Services\Reports;

use App\Models\ComplaintCategory;
use App\Models\Department;
use App\Models\Region;
use App\Repositories\Reports\PerformanceStatsRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class PerformanceStatsService
{
    public function __construct(
        protected PerformanceStatsRepositoryInterface $repo
    ) {
    }

    public function compute(array $filters): array
    {
        $from = $filters['from_at'] ?? $filters['from'] ?? null;
        $to   = $filters['to_at']   ?? $filters['to']   ?? null;

        $filters['from_at'] = $from instanceof CarbonInterface ? $from->copy() : Carbon::parse((string) $from);
        $filters['to_at']   = $to   instanceof CarbonInterface ? $to->copy()   : Carbon::parse((string) $to);

        $filters['from_at'] = $filters['from_at']->startOfDay();
        $filters['to_at']   = $filters['to_at']->endOfDay();

        $cacheKey = $this->cacheKey($filters);

        $ttl = now()->addMinutes(3);

        return Cache::remember($cacheKey, $ttl, function () use ($filters) {
            return $this->computeUncached($filters);
        });
    }

    protected function cacheKey(array $filters): string
    {
        // مهم: حول التاريخ لـ string حتى يصير key ثابت
        $payload = [
            'v' => 1, // بدّلها إذا عدّلت شكل الـ output لاحقاً
            'from' => $filters['from_at']->toDateString(),
            'to'   => $filters['to_at']->toDateString(),
            'department_id' => $filters['department_id'] ?? null,
            'region_id'     => $filters['region_id'] ?? null,
            'category_id'   => $filters['category_id'] ?? null,
        ];

        return 'stats:performance:' . sha1(json_encode($payload));
    }

    protected function computeUncached(array $filters): array
    {
        $totalCreated  = (int) $this->repo->countCreated($filters);
        $totalResolved = (int) $this->repo->countResolved($filters);
        $totalClosed   = (int) $this->repo->countClosed($filters);
        $totalOpen     = (int) $this->repo->countOpen($filters);

        $avgResolution = $this->repo->avgResolutionMinutes($filters);
        $slaMet        = (int) $this->repo->slaMetCount($filters);
        $slaBreached   = (int) $this->repo->slaBreachedCount($filters);
        $slaRate       = ($slaMet + $slaBreached) > 0 ? ($slaMet / ($slaMet + $slaBreached)) : null;

        $byStatusRaw    = $this->repo->groupByStatus($filters);
        $byPriorityRaw  = $this->repo->groupByPriority($filters);
        $byDeptRaw      = $this->repo->groupByDepartment($filters);
        $byRegionRaw    = $this->repo->groupByRegion($filters);
        $byCategoryRaw  = $this->repo->groupByCategory($filters);

        $createdRaw  = $this->repo->createdPerDay($filters);
        $resolvedRaw = $this->repo->resolvedPerDay($filters);

        [$createdPerDay, $resolvedPerDay] = $this->fillTrendDates(
            from: $filters['from_at'],
            to: $filters['to_at'],
            createdRaw: $createdRaw,
            resolvedRaw: $resolvedRaw
        );

        $deptIds = collect($byDeptRaw)->pluck('id')->filter()->unique()->values()->all();
        $regIds  = collect($byRegionRaw)->pluck('id')->filter()->unique()->values()->all();
        $catIds  = collect($byCategoryRaw)->pluck('id')->filter()->unique()->values()->all();

        $departments = Department::whereIn('id', $deptIds)->get(['id', 'name_en'])->keyBy('id');
        $regions     = Region::whereIn('id', $regIds)->get(['id', 'name_en'])->keyBy('id');
        $categories  = ComplaintCategory::whereIn('id', $catIds)->get(['id', 'label_en'])->keyBy('id');

        return [
            'filters' => [
                'from' => $filters['from_at']->toDateString(),
                'to'   => $filters['to_at']->toDateString(),
                'department_id' => $filters['department_id'] ?? null,
                'region_id'     => $filters['region_id'] ?? null,
                'category_id'   => $filters['category_id'] ?? null,
            ],
            'generated_at' => now()->toISOString(),

            'kpis' => [
                'total_created' => $totalCreated,
                'total_resolved' => $totalResolved,
                'total_closed' => $totalClosed,
                'total_open' => $totalOpen,
                'avg_resolution_minutes' => $avgResolution !== null ? (int) round($avgResolution) : null,
                'sla_met_count' => $slaMet,
                'sla_breached_count' => $slaBreached,
                'sla_met_rate' => $slaRate,
            ],

            'breakdowns' => [
                'by_status'   => $this->withPercentAndLabelsStatus($byStatusRaw, $totalCreated),
                'by_priority' => $this->withPercentAndLabelsPriority($byPriorityRaw, $totalCreated),

                'by_department' => collect($byDeptRaw)->map(function ($row) use ($departments, $totalCreated) {
                    $id = $row['id'] ?? null;
                    $nameEn = $id ? (($departments[$id]->name_en ?? null)) : null;

                    return [
                        'id' => $id,
                        'name' => $this->safeEnglish($nameEn, $id ? "Department #{$id}" : 'Unassigned'),
                        'count' => (int) $row['count'],
                        'percentage' => $totalCreated > 0 ? ((int) $row['count'] / $totalCreated) : 0,
                    ];
                })->values()->all(),

                'by_region' => collect($byRegionRaw)->map(function ($row) use ($regions, $totalCreated) {
                    $id = $row['id'] ?? null;
                    $nameEn = $id ? (($regions[$id]->name_en ?? null)) : null;

                    return [
                        'id' => $id,
                        'name' => $this->safeEnglish($nameEn, $id ? "Region #{$id}" : 'Unknown'),
                        'count' => (int) $row['count'],
                        'percentage' => $totalCreated > 0 ? ((int) $row['count'] / $totalCreated) : 0,
                    ];
                })->values()->all(),

                'by_category' => collect($byCategoryRaw)->map(function ($row) use ($categories, $totalCreated) {
                    $id = $row['id'] ?? null;
                    $labelEn = $id ? (($categories[$id]->label_en ?? null)) : null;

                    return [
                        'id' => $id,
                        'label' => $this->safeEnglish($labelEn, $id ? "Category #{$id}" : 'Unknown'),
                        'count' => (int) $row['count'],
                        'percentage' => $totalCreated > 0 ? ((int) $row['count'] / $totalCreated) : 0,
                    ];
                })->values()->all(),
            ],

            'trends' => [
                'created_per_day'  => $createdPerDay,
                'resolved_per_day' => $resolvedPerDay,
            ],
        ];
    }

    protected function safeEnglish(?string $value, string $fallback): string
    {
        $value = $value !== null ? trim($value) : '';
        if ($value === '') return $fallback;

        if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $value)) {
            return $fallback;
        }

        return $value;
    }

    protected function normalizeStatusKey(string $value): string
    {
        $keys = ['pending', 'needs_more_info', 'open', 'in_progress', 'resolved', 'closed', 'rejected'];
        if (in_array($value, $keys, true)) return $value;

        foreach ($keys as $k) {
            if ($value === Lang::get("complaints.status.$k", [], 'ar')) return $k;
            if ($value === Lang::get("complaints.status.$k", [], 'en')) return $k;
        }

        return $value;
    }

    protected function normalizePriorityKey(string $value): string
    {
        $keys = ['low', 'medium', 'high', 'urgent'];
        if (in_array($value, $keys, true)) return $value;

        foreach ($keys as $k) {
            if ($value === Lang::get("complaints.priority.$k", [], 'ar')) return $k;
            if ($value === Lang::get("complaints.priority.$k", [], 'en')) return $k;
        }

        return $value;
    }

    protected function statusLabel(string $value): string
    {
        $key = $this->normalizeStatusKey($value);

        return match ($key) {
            'pending' => 'Pending',
            'needs_more_info' => 'Needs More Info',
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    protected function priorityLabel(string $value): string
    {
        $key = $this->normalizePriorityKey($value);

        return match ($key) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Unknown',
        };
    }

    protected function withPercentAndLabelsStatus(array $rows, int $total): array
    {
        return collect($rows)->map(function ($r) use ($total) {
            $rawKey = (string) ($r['key'] ?? '');
            $count  = (int) ($r['count'] ?? 0);

            return [
                'key' => $rawKey,
                'label' => $this->statusLabel($rawKey),
                'count' => $count,
                'percentage' => $total > 0 ? ($count / $total) : 0,
            ];
        })->values()->all();
    }

    protected function withPercentAndLabelsPriority(array $rows, int $total): array
    {
        return collect($rows)->map(function ($r) use ($total) {
            $rawKey = (string) ($r['key'] ?? '');
            $count  = (int) ($r['count'] ?? 0);

            return [
                'key' => $rawKey,
                'label' => $this->priorityLabel($rawKey),
                'count' => $count,
                'percentage' => $total > 0 ? ($count / $total) : 0,
            ];
        })->values()->all();
    }

    protected function fillTrendDates(Carbon $from, Carbon $to, array $createdRaw, array $resolvedRaw): array
    {
        $createdMap  = collect($createdRaw)->keyBy('date')->map(fn ($r) => (int) $r['count']);
        $resolvedMap = collect($resolvedRaw)->keyBy('date')->map(fn ($r) => (int) $r['count']);

        $period = CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay());

        $created = [];
        $resolved = [];

        foreach ($period as $day) {
            $d = $day->toDateString();
            $created[]  = ['date' => $d, 'count' => (int) ($createdMap[$d] ?? 0)];
            $resolved[] = ['date' => $d, 'count' => (int) ($resolvedMap[$d] ?? 0)];
        }

        return [$created, $resolved];
    }
}
