<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceTokenRequest;
use App\Models\UserDeviceToken;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $token = UserDeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token'   => $data['fcm_token'],
            ],
            [
                'platform'    => $data['platform'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'message' => __('device_tokens.registered'),
            'data'    => [
                'id'      => $token->id,
                'token'   => $token->token,
                'platform' => $token->platform,
            ],
        ], 201);
    }
}
