<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'format' => $this->format,
            'status' => $this->status,

            'filters' => $this->filters,

            'queued_at' => $this->queued_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'finished_at' => $this->finished_at?->toISOString(),

            'file_size' => $this->file_size,
            'error_message' => $this->error_message,

            'download_url' => $this->status === 'success'
                ? route('admin.reports.download', $this->id)
                : null,
        ];
    }
}
