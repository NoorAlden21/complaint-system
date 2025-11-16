<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use App\Notifications\VerifyEmailCodeNotification;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\VerificationCode\VerificationCodeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $users,
        protected VerificationCodeRepositoryInterface $verificationCodes,
    ) {
    }

    public function registerCitizen(array $data): User
    {
        [$user, $rawCode] = DB::transaction(function () use ($data) {
            $user = $this->users->create([
                'name'         => $data['name'],
                'phone_number' => $data['phone_number'],
                'email'        => $data['email'],
                'password'     => $data['password'],
            ]);

            $user->assignRole('citizen');

            $rawCode = $this->generateCode();

            $this->verificationCodes->createForUser(
                user: $user,
                type: 'email_verification',
                hashedCode: Hash::make($rawCode),
                expiresAt: now()->addMinutes(60),
            );

            return [$user, $rawCode];
        });

        $user->notify(new VerifyEmailCodeNotification($rawCode));

        return $user;
    }


    public function login(array $credentials): array
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.invalid_credentials')],
            ]);
        }

        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => [__('auth.email_not_verified')],
            ]);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }


    public function verifyEmail(User $user, string $code): User
    {
        if ($user->email_verified_at) {
            return $user;
        }

        $verificationCode = $this->verificationCodes
            ->getLatestActiveCode($user, 'email_verification');

        if (!$verificationCode || $verificationCode->isExpired()) {
            throw ValidationException::withMessages([
                'code' => [__('auth.no_valid_code')],
            ]);
        }

        if (!Hash::check($code, $verificationCode->code)) {
            throw ValidationException::withMessages([
                'code' => [__('auth.invalid_code')],
            ]);
        }

        DB::transaction(function () use ($user, $verificationCode) {
            $this->verificationCodes->markAsUsed($verificationCode);

            $user->email_verified_at = now();
            $this->users->save($user);
        });

        return $user;
    }

    public function resendEmailVerification(User $user): void
    {
        if ($user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => [__('auth.email_already_verified')],
            ]);
        }

        $this->verificationCodes->invalidateActiveCodes($user, 'email_verification');

        $rawCode = $this->generateCode();

        $this->verificationCodes->createForUser(
            user: $user,
            type: 'email_verification',
            hashedCode: Hash::make($rawCode),
            expiresAt: now()->addMinutes(60),
        );

        $user->notify(new VerifyEmailCodeNotification($rawCode));
    }


    public function sendPasswordResetCode(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if (!$user) {
            return;
        }

        $rawCode = $this->generateCode();

        $this->verificationCodes->createForUser(
            user: $user,
            type: 'password_reset',
            hashedCode: Hash::make($rawCode),
            expiresAt: now()->addMinutes(15),
        );

        $user->notify(new PasswordResetCodeNotification($rawCode));
    }

    public function resetPassword(string $email, string $code, string $newPassword): array
    {
        $user = $this->users->findByEmail($email);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => [__('auth.password_reset_invalid_code')],
            ]);
        }

        $verificationCode = $this->verificationCodes
            ->getLatestActiveCode($user, 'password_reset');

        if (!$verificationCode || $verificationCode->isExpired() || !Hash::check($code, $verificationCode->code)) {
            throw ValidationException::withMessages([
                'code' => [__('auth.password_reset_invalid_code')],
            ]);
        }

        DB::transaction(function () use ($user, $verificationCode, $newPassword) {
            $this->verificationCodes->markAsUsed($verificationCode);

            $user->password = Hash::make($newPassword);
            $this->users->save($user);

            $user->tokens()->delete();
        });

        $token = $user->createToken('auth')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }


    protected function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
