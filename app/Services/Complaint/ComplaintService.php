<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\Region;
use App\Models\User;
use App\Repositories\Complaint\ComplaintRepositoryInterface;
use App\Repositories\ComplaintAttachment\ComplaintAttachmentRepositoryInterface;
use App\Repositories\ComplaintStatusHistory\ComplaintStatusHistoryRepositoryInterface;
use App\Repositories\Department\DepartmentRepositoryInterface;
use App\Repositories\ComplaintCategory\ComplaintCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaints,
        protected ComplaintStatusHistoryRepositoryInterface $statusHistories,
        protected DepartmentRepositoryInterface $departments,
        protected ComplaintCategoryRepositoryInterface $categories,
        protected ComplaintAttachmentRepositoryInterface $attachments,
    ) {
    }

    public function createComplaint(User $creator, array $data, array $attachments = []): Complaint
    {
        $complaint = DB::transaction(function () use ($creator, $data) {
            $data['status'] = 'pending';

            $complaint = $this->complaints->createForUser($creator, $data);

            $this->statusHistories->record(
                complaint: $complaint,
                fromStatus: null,
                toStatus: 'pending',
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

    protected function storeAttachments(User $uploader, Complaint $complaint, array $files): void
    {
        $disk = 'complaints'; // عرّفه في config/filesystems.php

        foreach ($files as $file) {
            $path = $file->store(
                'complaints/' . now()->format('Y/m') . '/' . $complaint->id,
                $disk
            );

            $this->attachments->createForComplaint($complaint, [
                'uploaded_by'   => $uploader->id,
                'disk'          => $disk,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'path'          => $path,
            ]);
        }
    }

    public function listForUser(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->complaints->paginateForUser($user, $filters, $perPage);
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
}
