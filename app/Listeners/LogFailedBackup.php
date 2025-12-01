<?php

namespace App\Listeners;

use App\Models\BackupLog;
use Spatie\Backup\Events\BackupHasFailed;

class LogFailedBackup
{
    /**
     * Handle the event.
     */
    public function handle(BackupHasFailed $event): void
    {
        $backupDestination = $event->backupDestination;

        BackupLog::create([
            'disk'         => $backupDestination?->diskName(),
            'backup_name'  => $backupDestination?->backupName(),
            'status'       => 'failed',
            'finished_at'  => now(),
            'error_message' => $event->exception?->getMessage(),
        ]);
    }
}
