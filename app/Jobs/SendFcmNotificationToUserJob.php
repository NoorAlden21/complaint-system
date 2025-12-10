<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmNotificationToUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10; //wait 10s before retrying

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $data = [],
    ) {
    }

    public function handle(FcmNotificationService $fcm): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $fcm->sendToUser($user, $this->title, $this->body, $this->data);
    }
}
