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
            'roles'          => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
