<?php

namespace App\Services\Backup;

use App\Jobs\RunBackupJob;
use App\Models\BackupLog;
use App\Repositories\Backup\BackupLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupService
{
    public function __construct(
        protected BackupLogRepositoryInterface $backups
    ) {
    }

    public function listBackups(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->backups->paginate($filters, $perPage);
    }

    public function getLastSuccessful(): ?BackupLog
    {
        return $this->backups->getLastSuccessful();
    }

    public function downloadBackup(BackupLog $backupLog): StreamedResponse
    {
        if ($backupLog->status !== 'success') {
            abort(400, 'Cannot download a failed backup.');
        }

        if (!$backupLog->disk || !$backupLog->path) {
            abort(404, 'Backup file information is missing.');
        }

        if (!Storage::disk($backupLog->disk)->exists($backupLog->path)) {
            abort(404, 'Backup file not found on disk.');
        }

        $downloadName = sprintf(
            '%s-%s.zip',
            $backupLog->backup_name ?? 'backup',
            $backupLog->finished_at?->format('Y-m-d_H-i-s') ?? $backupLog->id
        );

        return Storage::disk($backupLog->disk)->download($backupLog->path, $downloadName);
    }

    public function triggerBackup(): void
    {
        RunBackupJob::dispatch();
    }
}
