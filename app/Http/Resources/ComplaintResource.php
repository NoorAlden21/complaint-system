<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'reference_number' => $this->reference_number,

            'title'           => $this->title,
            'description'     => $this->description,

            'status'    => __('complaints.status.' . $this->status),

            'priority'  => __('complaints.priority.' . $this->priority),

            'category'        => new ComplaintCategoryResource($this->whenLoaded('category')),
            'department'      => new DepartmentResource($this->whenLoaded('department')),
            'region'          => new RegionResource($this->whenLoaded('region')),

            'attachments' => ComplaintAttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),

            'versions' => ComplaintVersionResource::collection(
                $this->whenLoaded('versions')
            ),

            'created_by'      => $this->created_by,

            'row_version'      => $this->row_version,

            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
