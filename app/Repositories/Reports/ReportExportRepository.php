<?php

namespace App\Repositories\Reports;

use App\Models\ReportExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportExportRepository implements ReportExportRepositoryInterface
{
    public function create(array $data): ReportExport
    {
        return ReportExport::create($data);
    }

    public function findById(int $id): ?ReportExport
    {
        return ReportExport::find($id);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = ReportExport::query()->latest('id');

        if (!empty($filters['type'])) $q->where('type', $filters['type']);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);

        $perPage = min(max((int) $perPage, 1), 100);
        return $q->paginate($perPage);
    }

    public function save(ReportExport $report): ReportExport
    {
        $report->save();
        return $report;
    }
}
