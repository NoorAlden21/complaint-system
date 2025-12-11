<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ComplaintAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'size'          => $this->size,
            'created_at'    => $this->created_at?->toISOString(),

            'url'           => $this->path
                ? Storage::disk('complaints')->url($this->path)
                : null,
            // 'url' => route('complaints.attachments.download', [$this->complaint_id, $this->id]),
        ];
    }
}
