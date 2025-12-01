<?php

namespace App\Listeners;

use App\Models\BackupLog;
use Spatie\Backup\Events\BackupWasSuccessful;

class LogSuccessfulBackup
{
    /**
     * Handle the event.
     */
    public function handle(BackupWasSuccessful $event): void
    {
        $backupDestination = $event->backupDestination;

        $backup = $backupDestination->newestBackup();

        BackupLog::create([
            'disk'          => $backupDestination->diskName(),
            'backup_name'   => $backupDestination->backupName(),
            'path'          => $backup?->path(),
            'size'          => $backup ? (int) $backup->sizeInBytes() : null,
            'status'        => 'success',
            'finished_at'   => now(),
            'error_message' => null,
        ]);
    }
}
