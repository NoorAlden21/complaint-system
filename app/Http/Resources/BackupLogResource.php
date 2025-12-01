<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'disk'         => $this->disk,
            'backup_name'  => $this->backup_name,
            'path'         => $this->path,
            'size'         => $this->size,
            'size_human'   => $this->size ? $this->humanSize($this->size) : null,
            'status'       => $this->status,
            'finished_at'  => $this->finished_at,
            'finished_at_human' => $this->finished_at?->diffForHumans(),
            'error_message' => $this->error_message,
            'created_at'   => $this->created_at,
        ];
    }

    protected function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
