<?php

namespace App\Services\Backup;

use App\Models\BackupLog;
use App\Support\Aop\AopRunner;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BackupService
{
    public function __construct(
        private BackupServiceCore $core,
        private AopRunner $runner
    ) {
    }

    public function listBackups(array $filters = [], int $perPage = 15)
    {
        return $this->runner->run(
            op: 'backup.list',
            fn: fn () => $this->core->listBackups($filters, $perPage),
            transactional: false
        );
    }

    public function getLastSuccessful(): ?BackupLog
    {
        return $this->runner->run(
            op: 'backup.last_success',
            fn: fn () => $this->core->getLastSuccessful(),
            transactional: false
        );
    }

    public function downloadBackup(BackupLog $backupLog): StreamedResponse
    {
        return $this->runner->run(
            op: 'backup.download',
            fn: fn () => $this->core->downloadBackup($backupLog),
            transactional: false,
            context: ['backup_id' => $backupLog->id]
        );
    }

    public function triggerBackup(): void
    {
        $this->runner->run(
            op: 'backup.trigger',
            fn: fn () => $this->core->triggerBackup(),
            transactional: false
        );
    }
}
