<?php

namespace App\Repositories\Reports;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PerformanceStatsRepository implements PerformanceStatsRepositoryInterface
{
    protected function applyCommonFilters(Builder $q, array $filters): Builder
    {
        if (!empty($filters['department_id'])) {
            $q->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['region_id'])) {
            $q->where('region_id', $filters['region_id']);
        }

        if (!empty($filters['category_id'])) {
            $q->where('category_id', $filters['category_id']);
        }

        return $q;
    }

    protected function baseCreatedQuery(array $filters): Builder
    {
        $q = Complaint::query()
            ->whereBetween('created_at', [$filters['from_at'], $filters['to_at']]);

        return $this->applyCommonFilters($q, $filters);
    }

    protected function baseResolvedQuery(array $filters): Builder
    {
        $q = Complaint::query()
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$filters['from_at'], $filters['to_at']]);

        return $this->applyCommonFilters($q, $filters);
    }

    protected function baseClosedQuery(array $filters): Builder
    {
        $q = Complaint::query()
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [$filters['from_at'], $filters['to_at']]);

        return $this->applyCommonFilters($q, $filters);
    }

    public function countCreated(array $filters): int
    {
        return (int) $this->baseCreatedQuery($filters)->count();
    }

    public function countResolved(array $filters): int
    {
        return (int) $this->baseResolvedQuery($filters)->count();
    }

    public function countClosed(array $filters): int
    {
        return (int) $this->baseClosedQuery($filters)->count();
    }

    public function countOpen(array $filters): int
    {
        return (int) $this->baseCreatedQuery($filters)
            ->whereIn('status', ['pending', 'needs_more_info', 'open', 'in_progress'])
            ->count();
    }

    public function avgResolutionMinutes(array $filters): ?float
    {
        $avg = $this->baseResolvedQuery($filters)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');

        return $avg !== null ? (float) $avg : null;
    }

    public function slaMetCount(array $filters): int
    {
        return (int) $this->baseResolvedQuery($filters)
            ->whereNotNull('sla_due_at')
            ->whereColumn('resolved_at', '<=', 'sla_due_at')
            ->count();
    }

    public function slaBreachedCount(array $filters): int
    {
        return (int) $this->baseResolvedQuery($filters)
            ->whereNotNull('sla_due_at')
            ->whereColumn('resolved_at', '>', 'sla_due_at')
            ->count();
    }

    public function groupByStatus(array $filters): array
    {
        return $this->baseCreatedQuery($filters)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['key' => $r->status, 'count' => (int) $r->count])
            ->all();
    }

    public function groupByPriority(array $filters): array
    {
        return $this->baseCreatedQuery($filters)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['key' => $r->priority, 'count' => (int) $r->count])
            ->all();
    }

    public function groupByDepartment(array $filters): array
    {
        return $this->baseCreatedQuery($filters)
            ->select('department_id', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['id' => $r->department_id, 'count' => (int) $r->count])
            ->all();
    }

    public function groupByRegion(array $filters): array
    {
        return $this->baseCreatedQuery($filters)
            ->select('region_id', DB::raw('COUNT(*) as count'))
            ->groupBy('region_id')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['id' => $r->region_id, 'count' => (int) $r->count])
            ->all();
    }

    public function groupByCategory(array $filters): array
    {
        return $this->baseCreatedQuery($filters)
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['id' => $r->category_id, 'count' => (int) $r->count])
            ->all();
    }

    public function createdPerDay(array $filters): array
    {
        return $this->baseCreatedQuery($filters)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => (string) $r->date, 'count' => (int) $r->count])
            ->all();
    }

    public function resolvedPerDay(array $filters): array
    {
        return $this->baseResolvedQuery($filters)
            ->selectRaw('DATE(resolved_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => (string) $r->date, 'count' => (int) $r->count])
            ->all();
    }
}
