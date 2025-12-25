<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'phone_number'      => $this->phone_number,
            'email'      => $this->email,
            'role'       => $this->roles->pluck('name')->first(),
            // 'department' => [
            //     'id'   => $this->department?->id,
            //     'name' => $this->department?->name,
            // ],

            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id'   => $this->department->id,
                    'name' => $this->department->name,
                ];
            }),

            'created_at' => $this->created_at,
        ];
    }
}
