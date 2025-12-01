<?php

namespace App\Repositories\Backup;

use App\Models\BackupLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BackupLogRepository implements BackupLogRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BackupLog::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('finished_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('finished_at', '<=', $filters['to']);
        }

        return $query
            ->orderByDesc('finished_at')
            ->paginate($perPage);
    }

    public function getLastSuccessful(): ?BackupLog
    {
        return BackupLog::where('status', 'success')
            ->orderByDesc('finished_at')
            ->first();
    }

    public function findByIdOrFail(int $id): BackupLog
    {
        return BackupLog::findOrFail($id);
    }
}
