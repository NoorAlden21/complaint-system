<?php

namespace App\Repositories\VerificationCode;

use App\Models\User;
use App\Models\VerificationCode;
use DateTimeInterface;

interface VerificationCodeRepositoryInterface
{
    public function createForUser(
        User $user,
        string $type,
        string $hashedCode,
        DateTimeInterface $expiresAt
    ): VerificationCode;

    public function getLatestActiveCode(User $user, string $type): ?VerificationCode;

    public function markAsUsed(VerificationCode $verificationCode): VerificationCode;

    public function invalidateActiveCodes(User $user, string $type): int;
}
