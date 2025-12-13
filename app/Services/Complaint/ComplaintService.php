<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\Region;
use App\Models\User;
use App\Repositories\Complaint\ComplaintRepositoryInterface;
use App\Repositories\ComplaintAttachment\ComplaintAttachmentRepositoryInterface;
use App\Repositories\ComplaintVersion\ComplaintVersionRepositoryInterface;
use App\Repositories\Department\DepartmentRepositoryInterface;
use App\Repositories\ComplaintCategory\ComplaintCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use App\Models\ComplaintNote;
use App\Models\ComplaintVersion;
use Illuminate\Validation\ValidationException;
use App\Services\UserNotificationService;

class ComplaintService
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaints,
        protected ComplaintVersionRepositoryInterface $complaintVersions,
        protected DepartmentRepositoryInterface $departments,
        protected ComplaintCategoryRepositoryInterface $categories,
        protected ComplaintAttachmentRepositoryInterface $attachments,
        protected UserNotificationService $userNotifications,
    ) {
    }

    public function createComplaint(User $creator, array $data, array $attachments = []): Complaint
    {
        $complaint = DB::transaction(function () use ($creator, $data) {
            $data['status'] = 'pending';
            $data['priority'] = 'medium';

            $complaint = $this->complaints->createForUser($creator, $data);
            $complaint->reference_number = $this->generateReferenceNumber($complaint);
            $complaint->save();

            $this->complaintVersions->record(
                complaint: $complaint,
                version_number: 1,
                changedBy: $creator->id,
                note: null
            );

            return $complaint;
        });

        if (!empty($attachments)) {
            $this->storeAttachments($creator, $complaint, $attachments, 1);
        }

        return $complaint->load(['category', 'department', 'attachments', 'region']);
    }

    protected function storeAttachments(User $uploader, Complaint $complaint, array $files, ?int $versionNumber = null): void
    {
        $disk = 'complaints';

        if ($versionNumber == null) {
            $versionNumber = $complaint->versions()->max('version_number') ?? 1;
        }

        foreach ($files as $file) {
            $path = $file->store(
                now()->format('Y/m/d') . '/' . $complaint->id,
                'complaints'
            );


            $this->attachments->createForComplaint($complaint, [
                'uploaded_by'   => $uploader->id,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'path'          => $path,
                'added_in_version' => $versionNumber
            ]);
        }
    }

    public function lockComplaint(User $user, int $complaintId, int $ttlMinutes = 15): Complaint
    {
        return DB::transaction(function () use ($user, $complaintId, $ttlMinutes) {
            $complaint = $this->complaints->findByIdForUpdate($complaintId);

            if (!$complaint) {
                throw new ModelNotFoundException('Complaint not found.');
            }

            if ($complaint->isLocked() && $complaint->locked_by !== $user->id) {
                throw ValidationException::withMessages([
                    'complaint' => ['هذه الشكوى مقفولة حاليًا من مستخدم آخر.'],
                ]);
            }

            $expiresAt = now()->addMinutes($ttlMinutes);

            return $this->complaints->lock($complaint, $user->id, $expiresAt);
        });
    }

    public function unlockComplaint(User $user, int $complaintId): Complaint
    {
        return DB::transaction(function () use ($user, $complaintId) {
            $complaint = $this->complaints->findByIdForUpdate($complaintId);

            if (!$complaint) {
                throw new ModelNotFoundException('Complaint not found.');
            }

            if (
                $complaint->isLocked() &&
                $complaint->locked_by !== $user->id &&
                !$user->hasRole('super_admin')
            ) {

                throw ValidationException::withMessages([
                    'complaint' => ['لا يمكنك فك قفل شكوى مقفولة من مستخدم آخر.'],
                ]);
            }

            return $this->complaints->unlock($complaint);
        });
    }

    public function list(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->complaints->paginateFor($user, $filters, $perPage);
    }

    public function getForUser(User $user, int $id): Complaint
    {
        $complaint = $this->complaints->findForUser($user, $id);

        if (!$complaint) {
            throw new ModelNotFoundException('Complaint not found or not accessible.');
        }

        return $complaint;
    }

    public function getCreateMetadata(User $user): array
    {
        $departments = $this->departments->allActive();
        $categories  = $this->categories->allActive();
        $regions = Region::all();


        return [
            'departments' => $departments,
            'categories'  => $categories,
            'regions' => $regions
        ];
    }

    public function updateComplaint(
        User $user,
        int $complaintId,
        array $data
    ): Complaint {
        return DB::transaction(function () use ($user, $complaintId, $data) {
            $expectedVersion = (int) ($data['row_version'] ?? -1); //optimistic lock

            $complaint = $this->complaints->findByIdForUpdate($complaintId);

            if (!$complaint) {
                throw new ModelNotFoundException('Complaint not found.');
            }

            //optimistic lock
            if ($expectedVersion !== $complaint->row_version) {
                throw ValidationException::withMessages([
                    'row_version' => [__('complaints.optimistic_lock_conflict')],
                ]);
            }

            //logical lock
            if ($user->hasRole('employee')) {
                if ($complaint->isLocked() && $complaint->locked_by !== $user->id) {
                    throw ValidationException::withMessages([
                        'complaint' => [__('complaints.locked_by_other')],
                    ]);
                }

                // extending the lock with every update
                if ($complaint->locked_by === $user->id) {
                    $this->complaints->lock($complaint, $user->id, now()->addMinutes(15));
                }
            }

            $changedFields = [];

            $this->applyStatusChange($complaint, $data, $changedFields);
            $this->applyPriorityChange($complaint, $data, $changedFields);
            $this->applyDepartmentChange($complaint, $data, $changedFields);

            if (empty($changedFields)) {
                return $complaint;
            }

            $complaint->row_version++;

            $complaint->save();

            $versionNote = $this->buildChangeNote(
                changedFields: $changedFields,
                customNote: $data['note'] ?? null
            );

            $version = $this->createVersionSnapshot(
                complaint: $complaint,
                user: $user,
                note: $versionNote,
            );

            if (
                isset($changedFields['status']) &&
                $complaint->status === 'needs_more_info' &&
                !empty($data['info_request_message'])
            ) {
                $this->createInfoRequestNote(
                    complaint: $complaint,
                    version: $version,
                    user: $user,
                    message: $data['info_request_message']
                );
            }

            $citizen = $complaint->creator;

            if ($citizen && isset($changedFields['status'])) {
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
            }

            if (isset($changedFields['department_id']) && $complaint->department_id) {
                $employees = User::role('employee')
                    ->where('department_id', $complaint->department_id)
                    ->get();

                $this->userNotifications->notifyUsers(
                    $employees,
                    type: 'complaint_reassigned',
                    title: __('notifications.complaints.reassigned.title'),
                    body: __('notifications.complaints.reassigned.body'),
                    data: [
                        'type'          => 'complaint_reassigned',
                        'complaint_id'  => (string) $complaint->id,
                    ]
                );
            }

            return $complaint;
        });
    }

    //public function restoreVersion()
    //{
    //}

    //update helpers

    protected function applyStatusChange(
        Complaint $complaint,
        array $data,
        array &$changedFields
    ): void {
        if (
            !array_key_exists('status', $data) ||
            $data['status'] === null ||
            $data['status'] === $complaint->status
        ) {
            return;
        }

        $originalStatus = $complaint->status;
        $newStatus      = $data['status'];

        $complaint->status = $newStatus;
        $changedFields['status'] = [$originalStatus, $newStatus];

        if ($newStatus === 'resolved' && is_null($complaint->resolved_at)) {
            $complaint->resolved_at = now();
        }

        if ($newStatus === 'closed' && is_null($complaint->closed_at)) {
            $complaint->closed_at = now();
        }
    }

    protected function applyPriorityChange(
        Complaint $complaint,
        array $data,
        array &$changedFields
    ): void {
        if (
            !array_key_exists('priority', $data) ||
            $data['priority'] === null ||
            $data['priority'] === $complaint->priority
        ) {
            return;
        }

        $originalPriority = $complaint->priority;
        $newPriority      = $data['priority'];

        $complaint->priority = $newPriority;
        $changedFields['priority'] = [$originalPriority, $newPriority];
    }

    protected function applyDepartmentChange(
        Complaint $complaint,
        array $data,
        array &$changedFields
    ): void {
        if (
            !array_key_exists('department_id', $data) ||
            $data['department_id'] === null ||
            $data['department_id'] === $complaint->department_id
        ) {
            return;
        }

        $originalDepartment = $complaint->department_id;
        $newDepartment      = $data['department_id'];

        $complaint->department_id = $newDepartment;
        $changedFields['department_id'] = [$originalDepartment, $newDepartment];
    }

    protected function buildChangeNote(
        array $changedFields,
        ?string $customNote = null
    ): string {
        if ($customNote !== null && trim($customNote) !== '') {
            return $customNote;
        }

        $parts = [];

        if (isset($changedFields['status'])) {
            [$from, $to] = $changedFields['status'];
            $parts[] = "تغيير الحالة من {$from} إلى {$to}";
        }

        if (isset($changedFields['priority'])) {
            [$from, $to] = $changedFields['priority'];
            $parts[] = "تغيير الأولوية من {$from} إلى {$to}";
        }

        if (isset($changedFields['department_id'])) {
            [$from, $to] = $changedFields['department_id'];
            $parts[] = "إعادة الإسناد من قسم ID={$from} إلى قسم ID={$to}";
        }

        if (empty($parts)) {
            return 'تعديل على بيانات الشكوى';
        }

        return 'تعديل الشكوى: ' . implode('، ', $parts);
    }

    protected function createInfoRequestNote(
        Complaint $complaint,
        ComplaintVersion $version,
        User $user,
        string $message
    ): void {
        ComplaintNote::create([
            'complaint_id'         => $complaint->id,
            'complaint_version_id' => $version->id,
            'created_by'           => $user->id,
            'type'                 => 'info_request',
            'is_internal'          => false,
            'message'              => $message,
        ]);
    }


    //helpers

    protected function createVersionSnapshot(
        Complaint $complaint,
        User $user,
        ?string $note = null,
        ?int $forcedVersionNumber = null
    ): ComplaintVersion {
        $currentMax = $complaint->versions()->max('version_number') ?? 0;
        $versionNumber = $forcedVersionNumber ?? ($currentMax + 1);

        return $this->complaintVersions->record(
            complaint: $complaint,
            version_number: $versionNumber,
            changedBy: $user->id,
            note: $note,
        );
    }

    protected function generateReferenceNumber(Complaint $complaint): string
    {
        return 'CMP-' . now()->format('Y') . '-' . str_pad($complaint->id, 6, '0', STR_PAD_LEFT);
    }
}
