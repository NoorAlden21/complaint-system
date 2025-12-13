<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'title' => $this->title,

            'status' => __('complaints.status.' . $this->status),
            'priority' => __('complaints.priority.' . $this->priority),

            'category' => new ComplaintCategoryResource($this->whenLoaded('category')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'region' => new RegionResource($this->whenLoaded('region')),

            'attachments_count' => $this->attachments_count ?? null,
            'notes_count' => $this->notes_count ?? null,
            'versions_count' => $this->versions_count ?? null,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
