<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->registerCitizen($request->validated());

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'تم إنشاء الحساب، برجاء إدخال كود التحقق المرسل إلى بريدك الإلكتروني.',
            'data'    => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $request->user();

        $user = $this->authService->verifyEmail(
            $user,
            $request->get('code'),
        );

        return response()->json([
            'message' => 'تم تفعيل البريد الإلكتروني بنجاح.',
            'data'    => new UserResource($user),
        ]);
    }
}
