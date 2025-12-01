<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResendVerificationEmailRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;


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
            'message' => __('auth.register_success'),
            'data'    => [
                'user'  => new UserResource($user),
                'token' => $token,
                'role' => $user->role,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => __('auth.login_success'),
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $request->user();

        $user = $this->authService->verifyEmail(
            $user,
            $request->get('code'),
        );

        return response()->json([
            'message' => __('auth.email_verified'),
            'data'    => new UserResource($user),
        ]);
    }

    public function resendVerificationEmail(ResendVerificationEmailRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authService->resendEmailVerification($user);

        return response()->json([
            'message' => __('auth.verification_email_resent'),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetCode(
            $request->validated()['email']
        );

        return response()->json([
            'message' => __('auth.password_reset_code_sent'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword(
            $request->validated()['email'],
            $request->validated()['code'],
            $request->validated()['password'],
        );

        return response()->json([
            'message' => __('auth.password_reset_success'),
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }
}
