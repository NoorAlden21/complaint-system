<?php

namespace App\Services\Auth;

use App\Models\User;
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
        return DB::transaction(function () use ($data) {
            $user = $this->users->create([
                'name'     => $data['name'],
                'phone_number' => $data['phone_number'],
                'email'    => $data['email'],
                'password' => $data['password'],
            ]);

            $user->assignRole('citizen');

            $rawCode = $this->generateCode();

            $this->verificationCodes->createForUser(
                user: $user,
                hashedCode: Hash::make($rawCode),
                expiresAt: now()->addMinutes(60),
            );

            $user->notify(new VerifyEmailCodeNotification($rawCode));

            return $user;
        });
    }

    public function verifyEmail(User $user, string $code): User
    {
        if ($user->email_verified_at) {
            return $user;
        }

        $verificationCode = $this->verificationCodes
            ->getLatestActiveCode($user);

        if (!$verificationCode || $verificationCode->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['لا يوجد كود تحقق صالح أو انتهت صلاحيته، برجاء طلب كود جديد.'],
            ]);
        }

        if (!Hash::check($code, $verificationCode->code)) {
            throw ValidationException::withMessages([
                'code' => ['كود التحقق غير صحيح.'],
            ]);
        }

        DB::transaction(function () use ($user, $verificationCode) {
            $this->verificationCodes->markAsUsed($verificationCode);

            $user->email_verified_at = now();
            $this->users->save($user);
        });

        return $user;
    }

    protected function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
