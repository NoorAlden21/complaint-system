<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\AccountLockedNotification;
use App\Notifications\PasswordResetCodeNotification;
use App\Notifications\VerifyEmailCodeNotification;
use App\Support\Aop\AopRunner;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    public function __construct(
        private AuthServiceCore $core,
        private AopRunner $runner,
    ) {
    }

    public function registerCitizen(array $data): User
    {
        [$user, $rawCode] = $this->runner->run(
            op: 'auth.register',
            fn: fn () => $this->core->registerCitizenDb($data),
            transactional: true,
            context: ['email' => $data['email'] ?? null]
        );

        $user->notify(new VerifyEmailCodeNotification($rawCode));

        return $user;
    }

    public function login(array $credentials): array
    {
        $user = $this->core->users->findByEmail($credentials['email']);

        if ($user && $user->isLocked()) {
            $minutes = $user->locked_until->diffInMinutes(now());

            throw ValidationException::withMessages([
                'email' => [__('auth.account_locked', ['minutes' => $minutes])],
            ]);
        }

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $state = $this->runner->run(
                    op: 'auth.login.fail',
                    fn: fn () => $this->core->recordFailedLoginAttemptDb($user),
                    transactional: true,
                    context: ['user_id' => $user->id]
                );

                if ($state['locked']) {
                    $user->notify(new AccountLockedNotification($state['locked_until']));

                    throw ValidationException::withMessages([
                        'email' => [__('auth.account_locked', ['minutes' => $this->core->lockMinutes])],
                    ]);
                }
            }

            throw ValidationException::withMessages([
                'email' => [__('auth.invalid_credentials')],
            ]);
        }

        $this->runner->run(
            op: 'auth.login.reset_state',
            fn: fn () => $this->core->clearLoginFailureStateDb($user),
            transactional: true,
            context: ['user_id' => $user->id]
        );

        if ($user->hasRole('citizen') && !$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => [__('auth.email_not_verified')],
            ]);
        }

        $token = $this->runner->run(
            op: 'auth.token.issue',
            fn: fn () => $this->core->issueTokenDb($user),
            transactional: true,
            context: ['user_id' => $user->id]
        );

        return [
            'user'  => $user->fresh(),
            'token' => $token,
        ];
    }

    public function verifyEmail(User $user, string $code): User
    {
        return $this->runner->run(
            op: 'auth.verify_email',
            fn: fn () => $this->core->verifyEmailDb($user, $code),
            transactional: true,
            context: ['user_id' => $user->id]
        );
    }

    public function resendEmailVerification(User $user): void
    {
        $rawCode = $this->runner->run(
            op: 'auth.resend_verify',
            fn: fn () => $this->core->resendEmailVerificationDb($user),
            transactional: true,
            context: ['user_id' => $user->id]
        );

        $user->notify(new VerifyEmailCodeNotification($rawCode));
    }

    public function sendPasswordResetCode(string $email): void
    {
        $user = $this->core->users->findByEmail($email);
        if (!$user) return;

        $rawCode = $this->runner->run(
            op: 'auth.password_reset.send_code',
            fn: fn () => $this->core->sendPasswordResetCodeDb($user),
            transactional: true,
            context: ['user_id' => $user->id]
        );

        $user->notify(new PasswordResetCodeNotification($rawCode));
    }

    public function resetPassword(string $email, string $code, string $newPassword): array
    {
        $user = $this->core->users->findByEmail($email);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => [__('auth.password_reset_invalid_code')],
            ]);
        }

        $this->runner->run(
            op: 'auth.password_reset.reset',
            fn: fn () => $this->core->resetPasswordDb($user, $code, $newPassword),
            transactional: true,
            context: ['user_id' => $user->id]
        );

        $token = $this->runner->run(
            op: 'auth.token.issue_after_reset',
            fn: fn () => $this->core->issueTokenDb($user),
            transactional: true,
            context: ['user_id' => $user->id]
        );

        return [
            'user'  => $user->fresh(),
            'token' => $token,
        ];
    }

    public function logout(User $user, bool $allDevices = false): void
    {
        $this->runner->run(
            op: 'auth.logout',
            fn: function () use ($user, $allDevices) {
                if ($allDevices) {
                    $user->tokens()->delete();
                    return;
                }

                $token = $user->currentAccessToken();
                if ($token) $token->delete();
            },
            transactional: true,
            context: ['user_id' => $user->id]
        );
    }
}
