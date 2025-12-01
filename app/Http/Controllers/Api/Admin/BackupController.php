<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupLogResource;
use App\Models\BackupLog;
use App\Services\Backup\BackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;


class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {
    }

    public function index(Request $request)
    {
        $filters = [
            'status' => $request->query('status'),
            'from'   => $request->query('from'),
            'to'     => $request->query('to'),
        ];

        $perPage = (int) $request->query('per_page', 15);

        $backups = $this->backupService->listBackups($filters, $perPage);

        return BackupLogResource::collection($backups);
    }

    public function lastSuccessful()
    {
        $backup = $this->backupService->getLastSuccessful();

        if (!$backup) {
            return response()->json([
                'message' => 'No successful backups found.',
            ], 404);
        }

        return new BackupLogResource($backup);
    }

    public function download(BackupLog $backupLog): StreamedResponse
    {
        return $this->backupService->downloadBackup($backupLog);
    }

    public function store()
    {
        $this->backupService->triggerBackup();

        return response()->json([
            'message' => __('backups.store'),
        ], 202);
    }
}
