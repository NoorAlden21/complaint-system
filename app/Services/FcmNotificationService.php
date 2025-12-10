<?php

namespace App\Services;

use App\Jobs\SendFcmNotificationToUserJob;
use App\Models\User;
use App\Models\UserDeviceToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmNotificationService
{
    public function __construct(
        protected Messaging $messaging
    ) {
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $notification = Notification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->toToken($token);

        $this->messaging->send($message);
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->deviceTokens()->pluck('token')->all();

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    public function sendToUsers(iterable $users, string $title, string $body, array $data = []): void
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data);
        }
    }

    public function queueToUser(User $user, string $title, string $body, array $data = []): void
    {
        SendFcmNotificationToUserJob::dispatch(
            $user->id,
            $title,
            $body,
            $data
        );
    }

    public function queueToUsers(iterable $users, string $title, string $body, array $data = []): void
    {
        foreach ($users as $user) {
            $this->queueToUser($user, $title, $body, $data);
        }
    }
}
