<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use App\Models\UserNotification;

class UserNotificationService
{
    public function __construct(
        protected FcmNotificationService $fcm
    ) {
    }

    public function notifyUser(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): UserNotification {
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);

        $this->fcm->queueToUser($user, $title, $body, $data);

        return $notification;
    }

    public function notifyUsers(
        iterable $users,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): void {
        foreach ($users as $user) {
            $this->notifyUser($user, $type, $title, $body, $data);
        }
    }

    public function notifyComplaintAudience(
        Complaint $complaint,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): void {
        $admins = User::role('super_admin')->get();

        $employees = User::role('employee')
            ->when($complaint->department_id, function ($q) use ($complaint) {
                $q->where('department_id', $complaint->department_id);
            }, function ($q) {
                $q->whereRaw('1=0');
            })
            ->get();

        $users = $admins->merge($employees)->unique('id')->values();

        if ($users->isEmpty()) {
            return;
        }

        $this->notifyUsers($users, $type, $title, $body, $data);
    }
}
