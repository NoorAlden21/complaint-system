<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Models\User as UserModel;
use App\Services\UserNotificationService;
use App\Support\Aop\AopRunner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

final class ComplaintService
{
    public function __construct(
        private ComplaintServiceCore $core,
        private AopRunner $runner,
        private UserNotificationService $userNotifications,
    ) {
    }

    private function safeNotify(\Closure $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::error('notification_failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function createComplaint(User $creator, array $data, array $attachments = []): Complaint
    {
        $complaint = $this->runner->run(
            op: 'complaint.create',
            fn: fn () => $this->core->createComplaintDb($creator, $data),
            transactional: true,
            context: ['user_id' => $creator->id]
        );

        if (!empty($attachments)) {
            $this->runner->run(
                op: 'complaint.create.attachments',
                fn: fn () => $this->core->storeAttachments($creator, $complaint, $attachments, 1),
                transactional: false,
                context: ['user_id' => $creator->id, 'complaint_id' => $complaint->id]
            );
        }

        $this->safeNotify(function () use ($complaint) {
            $this->userNotifications->notifyComplaintAudience(
                complaint: $complaint,
                type: 'complaint_created',
                title: __('notifications.complaints.created.title'),
                body: __('notifications.complaints.created.body', [
                    'reference' => $complaint->reference_number,
                ]),
                data: [
                    'type'          => 'complaint_created',
                    'complaint_id'  => (string) $complaint->id,
                    'status'        => $complaint->status,
                ]
            );
        });

        return $complaint->load(['category', 'department', 'attachments', 'region']);
    }

    public function lockComplaint(User $user, int $complaintId, int $ttlMinutes = 15): Complaint
    {
        return $this->runner->run(
            op: 'complaint.lock',
            fn: fn () => $this->core->lockComplaint($user, $complaintId, $ttlMinutes),
            transactional: true,
            context: ['user_id' => $user->id, 'complaint_id' => $complaintId]
        );
    }

    public function unlockComplaint(User $user, int $complaintId): Complaint
    {
        return $this->runner->run(
            op: 'complaint.unlock',
            fn: fn () => $this->core->unlockComplaint($user, $complaintId),
            transactional: true,
            context: ['user_id' => $user->id, 'complaint_id' => $complaintId]
        );
    }

    public function updateComplaint(User $user, int $complaintId, array $data): Complaint
    {
        $result = $this->runner->run(
            op: 'complaint.update',
            fn: fn () => $this->core->updateComplaintDb($user, $complaintId, $data),
            transactional: true,
            context: ['user_id' => $user->id, 'complaint_id' => $complaintId]
        );

        /** @var Complaint $complaint */
        $complaint = $result['complaint'];
        $changedFields = $result['changed_fields'] ?? [];

        if (!empty($changedFields['status'])) {
            $citizen = $complaint->creator;

            if ($citizen) {
                $this->safeNotify(function () use ($complaint, $citizen) {
                    if ($complaint->status === 'needs_more_info') {
                        $this->userNotifications->notifyUser(
                            $citizen,
                            type: 'complaint_more_info_requested',
                            title: __('notifications.complaints.more_info_requested.title'),
                            body: __('notifications.complaints.more_info_requested.body', [
                                'reference' => $complaint->reference_number,
                            ]),
                            data: [
                                'type'          => 'complaint_more_info_requested',
                                'complaint_id'  => (string) $complaint->id,
                                'status'        => $complaint->status,
                            ]
                        );
                    } else {
                        $this->userNotifications->notifyUser(
                            $citizen,
                            type: 'complaint_status_changed',
                            title: __('notifications.complaints.status_changed.title'),
                            body: __('notifications.complaints.status_changed.body', [
                                'reference' => $complaint->reference_number,
                                'status'    => __('complaints.status.' . $complaint->status),
                            ]),
                            data: [
                                'type'          => 'complaint_status_changed',
                                'complaint_id'  => (string) $complaint->id,
                                'status'        => $complaint->status,
                            ]
                        );
                    }
                });
            }
        }

        // notify new department employees if department changed
        if (!empty($changedFields['department_id']) && $complaint->department_id) {

            $this->safeNotify(function () use ($complaint) {
                $this->userNotifications->notifyComplaintAudience(
                    complaint: $complaint,
                    type: 'complaint_reassigned',
                    title: __('notifications.complaints.reassigned.title'),
                    body: __('notifications.complaints.reassigned.body'),
                    data: [
                        'type'         => 'complaint_reassigned',
                        'complaint_id' => (string) $complaint->id,
                    ]
                );
            });
        }

        return $complaint->refresh()->load(['category', 'department', 'attachments', 'region', 'versions.notes']);
    }

    public function replyToInfoRequest(User $user, int $complaintId, string $message, array $attachments = []): Complaint
    {
        [$complaint, $versionNumber] = $this->runner->run(
            op: 'complaint.reply_info',
            fn: fn () => (function () use ($user, $complaintId, $message) {
                [$complaint, $version] = $this->core->replyToInfoRequestDb(
                    user: $user,
                    complaintId: $complaintId,
                    message: $message
                );

                return [$complaint, (int) $version->version_number];
            })(),
            transactional: true,
            context: ['user_id' => $user->id, 'complaint_id' => $complaintId]
        );

        if (!empty($attachments)) {
            $this->runner->run(
                op: 'complaint.reply_info.attachments',
                fn: fn () => $this->core->storeAttachments(
                    uploader: $user,
                    complaint: $complaint,
                    files: $attachments,
                    versionNumber: $versionNumber
                ),
                transactional: false,
                context: [
                    'user_id' => $user->id,
                    'complaint_id' => $complaint->id,
                    'version_number' => $versionNumber,
                ]
            );
        }

        $this->safeNotify(function () use ($complaint) {
            $this->userNotifications->notifyComplaintAudience(
                complaint: $complaint,
                type: 'complaint_info_replied',
                title: __('notifications.complaints.info_replied.title'),
                body: __('notifications.complaints.info_replied.body', [
                    'reference' => $complaint->reference_number,
                ]),
                data: [
                    'type'         => 'complaint_info_replied',
                    'complaint_id' => (string) $complaint->id,
                    'status'       => $complaint->status,
                ]
            );
        });

        return $complaint->refresh()
            ->load(['category', 'department', 'attachments', 'region', 'versions.notes']);
    }

    public function list(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->runner->run(
            op: 'complaint.list',
            fn: fn () => $this->core->list($user, $filters, $perPage),
            transactional: false,
            context: ['user_id' => $user->id]
        );
    }

    public function getForUser(User $user, int $id): Complaint
    {
        return $this->runner->run(
            op: 'complaint.show',
            fn: fn () => $this->core->getForUser($user, $id),
            transactional: false,
            context: ['user_id' => $user->id, 'complaint_id' => $id]
        );
    }

    public function restoreComplaintVersion(
        User $user,
        int $complaintId,
        int $versionNumber,
        int $rowVersion,
        ?string $note = null
    ): Complaint {
        $complaint = $this->runner->run(
            op: 'complaint.restore_version',
            fn: fn () => $this->core->restoreComplaintVersionDb(
                user: $user,
                complaintId: $complaintId,
                versionNumber: $versionNumber,
                expectedRowVersion: $rowVersion,
                note: $note
            ),
            transactional: true,
            context: [
                'user_id' => $user->id,
                'complaint_id' => $complaintId,
                'target_version' => $versionNumber,
            ]
        );

        $this->safeNotify(function () use ($complaint, $versionNumber) {
            $this->userNotifications->notifyComplaintAudience(
                complaint: $complaint,
                type: 'complaint_restored',
                title: __('notifications.complaints.restored.title'),
                body: __('notifications.complaints.restored.body', [
                    'reference' => $complaint->reference_number,
                    'version'   => $versionNumber,
                ]),
                data: [
                    'type'         => 'complaint_restored',
                    'complaint_id' => (string) $complaint->id,
                    'version'      => (string) $versionNumber,
                    'status'       => $complaint->status,
                ]
            );
        });

        return $complaint->refresh()
            ->load(['category', 'department', 'attachments', 'region', 'versions.notes', 'locker']);
    }


    public function getCreateMetadata(User $user): array
    {
        return $this->runner->run(
            op: 'complaint.meta',
            fn: fn () => $this->core->getCreateMetadata($user),
            transactional: false,
            context: ['user_id' => $user->id]
        );
    }
}
