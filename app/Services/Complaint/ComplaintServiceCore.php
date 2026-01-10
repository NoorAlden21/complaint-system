<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Region;
use App\Models\User;
use App\Repositories\Complaint\ComplaintRepositoryInterface;
use App\Repositories\ComplaintAttachment\ComplaintAttachmentRepositoryInterface;
use App\Repositories\ComplaintVersion\ComplaintVersionRepositoryInterface;
use App\Repositories\Department\DepartmentRepositoryInterface;
use App\Repositories\ComplaintCategory\ComplaintCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\ComplaintNote;
use App\Models\ComplaintVersion;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class ComplaintServiceCore
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaints,
        protected ComplaintVersionRepositoryInterface $complaintVersions,
        protected DepartmentRepositoryInterface $departments,
        protected ComplaintCategoryRepositoryInterface $categories,
        protected ComplaintAttachmentRepositoryInterface $attachments,
    ) {
    }

    public function createComplaintDb(User $creator, array $data): Complaint
    {
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
    }

    public function storeAttachments(User $uploader, Complaint $complaint, array $files, ?int $versionNumber = null): void
    {
        if ($versionNumber === null) {
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
    }

    public function unlockComplaint(User $user, int $complaintId): Complaint
    {
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
    }

    public function list(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
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

    public function restoreComplaintVersionDb(
        User $user,
        int $complaintId,
        int $versionNumber,
        int $expectedRowVersion,
        ?string $note = null
    ): Complaint {
        $complaint = $this->complaints->findByIdForUpdate($complaintId);

        if (!$complaint) {
            throw new ModelNotFoundException('Complaint not found.');
        }

        if ($user->hasRole('employee')) {
            if ((int) $complaint->department_id !== (int) $user->department_id) {
                throw new ModelNotFoundException('Complaint not found or not accessible.');
            }

            if (!$complaint->isLocked() || (int) $complaint->locked_by !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'complaint' => ['يجب أن تكون الشكوى مقفولة عليك قبل الاستعادة.'],
                ]);
            }
        }

        $currentRowVersion = (int) ($complaint->row_version ?? 0);
        if ($expectedRowVersion !== $currentRowVersion) {
            throw ValidationException::withMessages([
                'row_version' => [__('complaints.optimistic_lock_conflict')],
            ]);
        }

        $target = $this->complaintVersions->findByComplaintAndNumber($complaintId, $versionNumber);

        if (!$target) {
            throw new ModelNotFoundException('Target version not found.');
        }

        if ($user->hasRole('employee')) {
            if ((int) $target->department_id !== (int) $user->department_id) {
                throw ValidationException::withMessages([
                    'version' => ['لا يمكنك استعادة نسخة تابعة لقسم مختلف.'],
                ]);
            }
        }

        $fields = [
            'status', 'title', 'description',
            'category_id', 'department_id', 'region_id', 'priority',
        ];

        $hasChanges = false;
        foreach ($fields as $f) {
            if ($complaint->{$f} !== $target->{$f}) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            return $complaint;
        }

        $complaint->status        = $target->status;
        $complaint->title         = $target->title;
        $complaint->description   = $target->description;
        $complaint->category_id   = $target->category_id;
        $complaint->department_id = $target->department_id;
        $complaint->region_id     = $target->region_id;
        $complaint->priority      = $target->priority;

        if ($complaint->status !== 'resolved') {
            $complaint->resolved_at = null;
        } elseif ($complaint->resolved_at === null) {
            $complaint->resolved_at = now();
        }

        if ($complaint->status !== 'closed') {
            $complaint->closed_at = null;
        } elseif ($complaint->closed_at === null) {
            $complaint->closed_at = now();
        }

        $complaint->row_version = $currentRowVersion + 1;
        $complaint->save();

        $restoreNote = "تمت الاستعادة إلى الإصدار رقم {$versionNumber}";
        if ($note !== null && trim($note) !== '') {
            $restoreNote .= ' - ' . trim($note);
        }

        $newVersion = $this->createVersionSnapshot(
            complaint: $complaint,
            user: $user,
            note: $restoreNote,
        );


        ComplaintNote::create([
            'complaint_id'         => $complaint->id,
            'complaint_version_id' => $newVersion->id,
            'created_by'           => $user->id,
            'type'                 => 'note',
            'is_internal'          => true,
            'message'              => $restoreNote,
        ]);

        return $complaint;
    }


    public function getCreateMetadata(User $user): array
    {
        $locale = app()->getLocale();
        $cacheKey = "complaints:meta:create:{$locale}:v1";

        return Cache::rememberForever($cacheKey, function () {
            return [
                'departments' => $this->departments->allActive(),
                'categories'  => $this->categories->allActive(),
                'regions'     => Region::query()->orderBy('id')->get(),
            ];
        });
    }

    /**
     *
     * @return array{complaint: Complaint, changed_fields: array}
     */
    public function updateComplaintDb(User $user, int $complaintId, array $data): array
    {
        $expectedVersion = (int) ($data['row_version'] ?? -1);

        $complaint = $this->complaints->findByIdForUpdate($complaintId);

        if (!$complaint) {
            throw new ModelNotFoundException('Complaint not found.');
        }

        if ($expectedVersion !== $complaint->row_version) {
            throw ValidationException::withMessages([
                'row_version' => [__('complaints.optimistic_lock_conflict')],
            ]);
        }

        if ($user->hasRole('employee')) {
            if ($complaint->isLocked() && $complaint->locked_by !== $user->id) {
                throw ValidationException::withMessages([
                    'complaint' => [__('complaints.locked_by_other')],
                ]);
            }

            if ($complaint->locked_by === $user->id) {
                $this->complaints->lock($complaint, $user->id, now()->addMinutes(1));
            }
        }

        $changedFields = [];

        $this->applyStatusChange($complaint, $data, $changedFields);
        $this->applyPriorityChange($complaint, $data, $changedFields);
        $this->applyDepartmentChange($complaint, $data, $changedFields);

        if (empty($changedFields)) {
            return ['complaint' => $complaint, 'changed_fields' => []];
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

        return [
            'complaint' => $complaint,
            'changed_fields' => $changedFields,
        ];
    }

    public function replyToInfoRequestDb(User $user, int $complaintId, string $message): array
    {
        $complaint = $this->complaints->findByIdForUpdate($complaintId);

        if (!$complaint) {
            throw new ModelNotFoundException('Complaint not found.');
        }

        if ((int) $complaint->created_by !== (int) $user->id) {
            throw new ModelNotFoundException('Complaint not found or not accessible.');
        }

        if ($complaint->status !== 'needs_more_info') {
            throw ValidationException::withMessages([
                'complaint' => [__('complaints.reply_only_when_needs_more_info')],
            ]);
        }

        $complaint->row_version++;
        $complaint->save();

        $version = $this->createVersionSnapshot(
            complaint: $complaint,
            user: $user,
            note: __('complaints.version_notes.info_reply'),
        );

        $this->createInfoReplyNote(
            complaint: $complaint,
            version: $version,
            user: $user,
            message: $message
        );

        return [$complaint, $version];
    }

    protected function createInfoReplyNote(Complaint $complaint, ComplaintVersion $version, User $user, string $message): void
    {
        ComplaintNote::create([
            'complaint_id'         => $complaint->id,
            'complaint_version_id' => $version->id,
            'created_by'           => $user->id,
            'type'                 => 'info_reply',
            'is_internal'          => false,
            'message'              => $message,
        ]);
    }


    protected function applyStatusChange(Complaint $complaint, array $data, array &$changedFields): void
    {
        if (!array_key_exists('status', $data) || $data['status'] === null || $data['status'] === $complaint->status) {
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

    protected function applyPriorityChange(Complaint $complaint, array $data, array &$changedFields): void
    {
        if (!array_key_exists('priority', $data) || $data['priority'] === null || $data['priority'] === $complaint->priority) {
            return;
        }

        $originalPriority = $complaint->priority;
        $newPriority      = $data['priority'];

        $complaint->priority = $newPriority;
        $changedFields['priority'] = [$originalPriority, $newPriority];
    }

    protected function applyDepartmentChange(Complaint $complaint, array $data, array &$changedFields): void
    {
        if (!array_key_exists('department_id', $data) || $data['department_id'] === null || $data['department_id'] === $complaint->department_id) {
            return;
        }

        $originalDepartment = $complaint->department_id;
        $newDepartment      = $data['department_id'];

        $complaint->department_id = $newDepartment;
        $changedFields['department_id'] = [$originalDepartment, $newDepartment];
    }

    protected function buildChangeNote(array $changedFields, ?string $customNote = null): string
    {
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

    protected function createInfoRequestNote(Complaint $complaint, ComplaintVersion $version, User $user, string $message): void
    {
        ComplaintNote::create([
            'complaint_id'         => $complaint->id,
            'complaint_version_id' => $version->id,
            'created_by'           => $user->id,
            'type'                 => 'info_request',
            'is_internal'          => false,
            'message'              => $message,
        ]);
    }

    protected function createVersionSnapshot(Complaint $complaint, User $user, ?string $note = null, ?int $forcedVersionNumber = null): ComplaintVersion
    {
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
