<?php

namespace App\Repositories\Backup;

use App\Models\BackupLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BackupLogRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getLastSuccessful(): ?BackupLog;

    public function findByIdOrFail(int $id): BackupLog;
}
