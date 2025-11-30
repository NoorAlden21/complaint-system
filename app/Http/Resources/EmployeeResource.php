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
            'email'      => $this->email,
            'role'       => $this->roles->pluck('name')->first(),
            // 'department' => [
            //     'id'   => $this->department?->id,
            //     'name' => $this->department?->name,
            // ],
            'phone'      => $this->employeeProfile->phone,
            'created_at' => $this->created_at,
        ];
    }
}
