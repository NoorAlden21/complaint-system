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

            'created_by'      => $this->created_by,

            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
