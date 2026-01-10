<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\AccountLockedNotification;
use App\Notifications\PasswordResetCodeNotification;
use App\Notifications\VerifyEmailCodeNotification;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\VerificationCode\VerificationCodeRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthServiceCore
{
    public int $maxFailedAttempts = 5;
    public int $lockMinutes       = 10;

    public function __construct(
        public UserRepositoryInterface $users,
        public VerificationCodeRepositoryInterface $verificationCodes,
    ) {
    }

    public function registerCitizenDb(array $data): array
    {
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
    }

    public function verifyEmailDb(User $user, string $code): User
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

        $this->verificationCodes->markAsUsed($verificationCode);

        $user->email_verified_at = now();
        $this->users->save($user);

        return $user;
    }

    public function resendEmailVerificationDb(User $user): string
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

        return $rawCode;
    }

    public function sendPasswordResetCodeDb(User $user): string
    {
        $rawCode = $this->generateCode();

        $this->verificationCodes->createForUser(
            user: $user,
            type: 'password_reset',
            hashedCode: Hash::make($rawCode),
            expiresAt: now()->addMinutes(15),
        );

        return $rawCode;
    }

    public function resetPasswordDb(User $user, string $code, string $newPassword): void
    {
        $verificationCode = $this->verificationCodes
            ->getLatestActiveCode($user, 'password_reset');

        if (!$verificationCode || $verificationCode->isExpired() || !Hash::check($code, $verificationCode->code)) {
            throw ValidationException::withMessages([
                'code' => [__('auth.password_reset_invalid_code')],
            ]);
        }

        $this->verificationCodes->markAsUsed($verificationCode);

        $user->password = Hash::make($newPassword);
        $this->users->save($user);

        $user->tokens()->delete();
    }

    public function recordFailedLoginAttemptDb(User $user): array
    {
        $user->failed_login_attempts = ($user->failed_login_attempts  ?? 0) + 1;

        $locked = false;
        $lockedUntil = null;

        if ($user->failed_login_attempts >= $this->maxFailedAttempts) {
            $user->locked_until = now()->addMinutes($this->lockMinutes);
            $user->failed_login_attempts = 0;

            $locked = true;
            $lockedUntil = $user->locked_until;
        }

        $this->users->save($user);

        return [
            'locked' => $locked,
            'locked_until' => $lockedUntil,
        ];
    }

    public function clearLoginFailureStateDb(User $user): void
    {
        if (($user->failed_login_attempts ?? 0) > 0 || $user->locked_until) {
            $user->failed_login_attempts = 0;
            $user->locked_until = null;
            $this->users->save($user);
        }
    }

    public function issueTokenDb(User $user): string
    {
        return $user->createToken('auth')->plainTextToken;
    }

    public function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
