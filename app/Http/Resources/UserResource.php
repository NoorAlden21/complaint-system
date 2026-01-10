<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'phone_number'   => $this->phone_number,
            'email'          => $this->email,
            'email_verified' => (bool) $this->email_verified_at,

            'role' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->first()),

            'complaints_count' => $this->when(isset($this->complaints_count), $this->complaints_count),

            'complaints' => ComplaintSummaryResource::collection(
                $this->whenLoaded('complaints')
            ),

            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
