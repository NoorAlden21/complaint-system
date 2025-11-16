<?php

namespace App\Repositories\VerificationCode;

use App\Models\User;
use App\Models\VerificationCode;
use DateTimeInterface;

class VerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function createForUser(
        User $user,
        string $type,
        string $hashedCode,
        DateTimeInterface $expiresAt
    ): VerificationCode {
        return VerificationCode::create([
            'user_id'    => $user->id,
            'type'       => $type,
            'code'       => $hashedCode,
            'expires_at' => $expiresAt,
        ]);
    }

    public function getLatestActiveCode(User $user, string $type): ?VerificationCode
    {
        return VerificationCode::where('user_id', $user->id)
            ->where('type', $type)
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

    public function invalidateActiveCodes(User $user, string $type): int
    {
        return VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update([
                'used_at' => now(),
            ]);
    }
}
