<?php

namespace App\Repositories\VerificationCode;

use App\Models\User;
use App\Models\VerificationCode;
use DateTimeInterface;

class VerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function createForUser(
        User $user,
        string $hashedCode,
        DateTimeInterface $expiresAt
    ): VerificationCode {
        return VerificationCode::create([
            'user_id'    => $user->id,
            'code'       => $hashedCode,
            'expires_at' => $expiresAt,
        ]);
    }

    public function getLatestActiveCode(User $user): ?VerificationCode
    {
        return VerificationCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();
    }

    public function markAsUsed(VerificationCode $verificationCode): VerificationCode
    {
        $verificationCode->used_at = now();
        $verificationCode->save();

        return $verificationCode;
    }
}
