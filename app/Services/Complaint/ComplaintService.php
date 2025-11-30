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

class ComplaintService
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaints,
        protected ComplaintVersionRepositoryInterface $complaintVersions,
        protected DepartmentRepositoryInterface $departments,
        protected ComplaintCategoryRepositoryInterface $categories,
        protected ComplaintAttachmentRepositoryInterface $attachments,
    ) {
    }

    public function createComplaint(User $creator, array $data, array $attachments = []): Complaint
    {
        $complaint = DB::transaction(function () use ($creator, $data) {
            $data['status'] = 'pending';
            $date['priority'] = 'medium';

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
            $this->storeAttachments($creator, $complaint, $attachments);
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
                'complaints/' . now()->format('Y/m/d') . '/' . $complaint->id,
                $disk
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
            $complaint = $this->complaints->findById($complaintId);

            if (!$complaint) {
                throw new ModelNotFoundException('Complaint not found.');
            }

            $originalStatus   = $complaint->status;
            $originalPriority = $complaint->priority;

            $changedFields = [];

            if (array_key_exists('status', $data) && $data['status'] !== null && $data['status'] !== $complaint->status) {
                $complaint->status = $data['status'];
                $changedFields['status'] = [$originalStatus, $data['status']];

                if ($data['status'] === 'resolved' && is_null($complaint->resolved_at)) {
                    $complaint->resolved_at = now();
                }

                if ($data['status'] === 'closed' && is_null($complaint->closed_at)) {
                    $complaint->closed_at = now();
                }
            }

            if (array_key_exists('priority', $data) && $data['priority'] !== null && $data['priority'] !== $complaint->priority) {
                $complaint->priority = $data['priority'];
                $changedFields['priority'] = [$originalPriority, $data['priority']];
            }

            if (empty($changedFields)) {
                return $complaint;
            }

            $complaint->save();

            $parts = [];
            if (isset($changedFields['status'])) {
                [$from, $to] = $changedFields['status'];
                $parts[] = "تغيير الحالة من {$from} إلى {$to}";
            }
            if (isset($changedFields['priority'])) {
                [$from, $to] = $changedFields['priority'];
                $parts[] = "تغيير الأولوية من {$from} إلى {$to}";
            }

            $note = 'تعديل الشكوى: ' . implode('، ', $parts);

            $this->createVersionSnapshot(
                complaint: $complaint,
                user: $user,
                note: $note,
            );

            return $complaint;
        });
    }

    public function reassignComplaint(
        User $user,
        int $complaintId,
        int $departmentId,
        ?string $note = null
    ): Complaint {
        return DB::transaction(function () use ($user, $complaintId, $departmentId, $note) {
            $complaint = $this->complaints->findById($complaintId);

            if (!$complaint) {
                throw new ModelNotFoundException('Complaint not found.');
            }

            $oldDepartmentId = $complaint->department_id;

            if ($oldDepartmentId === $departmentId) {
                return $complaint;
            }

            $complaint->department_id = $departmentId;
            $complaint->save();

            $versionNote = $note ?: "إعادة إسناد الشكوى من قسم ID={$oldDepartmentId} إلى قسم ID={$departmentId}";

            $this->createVersionSnapshot(
                complaint: $complaint,
                user: $user,
                note: $versionNote,
            );

            if ($note) {
                ComplaintNote::create([
                    'complaint_id'         => $complaint->id,
                    'complaint_version_id' => $complaint->versions()->latest('version_number')->first()?->id,
                    'created_by'           => $user->id,
                    'type'                 => 'note',
                    'is_internal'          => true,
                    'message'              => $note,
                ]);
            }

            return $complaint;
        });
    }

    public function requestMoreInfo(
        User $user,
        int $complaintId,
        string $message
    ): Complaint {
        return DB::transaction(function () use ($user, $complaintId, $message) {
            $complaint = $this->complaints->findById($complaintId);

            if (!$complaint) {
                throw new ModelNotFoundException('Complaint not found.');
            }

            if (!in_array($complaint->status, ['pending', 'open', 'in_progress', 'needs_more_info'])) {
                throw new \RuntimeException('Cannot request more info for this complaint status.');
            }

            $originalStatus = $complaint->status;
            $complaint->status = 'needs_more_info';
            $complaint->save();

            // نسوي نسخة جديدة
            $version = $this->createVersionSnapshot(
                complaint: $complaint,
                user: $user,
                note: "طلب معلومات إضافية من المواطن (من حالة {$originalStatus} إلى needs_more_info)",
            );

            ComplaintNote::create([
                'complaint_id'         => $complaint->id,
                'complaint_version_id' => $version->id,
                'created_by'           => $user->id,
                'type'                 => 'info_request',
                'is_internal'          => false,
                'message'              => $message,
            ]);

            return $complaint;
        });
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
