<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:500'],
            'platform'  => ['nullable', 'string', 'max:20'], // android / ios / web
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
