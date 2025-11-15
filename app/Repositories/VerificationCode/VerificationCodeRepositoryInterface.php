<?php

namespace App\Repositories\VerificationCode;

use App\Models\User;
use App\Models\VerificationCode;
use DateTimeInterface;

interface VerificationCodeRepositoryInterface
{
    public function createForUser(
        User $user,
        string $hashedCode,
        DateTimeInterface $expiresAt
    ): VerificationCode;

    public function getLatestActiveCode(User $user): ?VerificationCode;

    public function markAsUsed(VerificationCode $verificationCode): VerificationCode;
}
