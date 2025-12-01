<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'version_number' => $this->version_number,

            'title'          => $this->title,
            'description'    => $this->description,

            'status'   => __('complaints.status.' . $this->status),
            'priority' => $this->priority
                ? __('complaints.priority.' . $this->priority)
                : __('complaints.priority.medium'),


            'category_id'    => $this->category_id,
            'department_id'  => $this->department_id,
            'region_id'      => $this->region_id,

            'changed_by'     => $this->changed_by,
            'note'           => $this->note,

            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
