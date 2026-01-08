<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Support\Aop\AopRunner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ComplaintService
{
    public function __construct(
        private ComplaintServiceCore $core,
        private AopRunner $runner,
    ) {
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
        return $this->runner->run(
            op: 'complaint.update',
            fn: fn () => $this->core->updateComplaint($user, $complaintId, $data),
            transactional: true,
            context: ['user_id' => $user->id, 'complaint_id' => $complaintId]
        );
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
