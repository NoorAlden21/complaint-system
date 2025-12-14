<?php

namespace App\Repositories\Reports;

use App\Models\ReportExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReportExportRepositoryInterface
{
    public function create(array $data): ReportExport;

    public function findById(int $id): ?ReportExport;

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function save(ReportExport $report): ReportExport;
}
