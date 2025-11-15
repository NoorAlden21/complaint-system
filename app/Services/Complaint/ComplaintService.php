<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Repositories\Complaint\ComplaintRepositoryInterface;
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
    ) {
    }

    public function createComplaint(User $creator, array $data): Complaint
    {
        return DB::transaction(function () use ($creator, $data) {
            $data['status'] = 'pending';

            $complaint = $this->complaints->createForUser($creator, $data);

            $this->statusHistories->record(
                complaint: $complaint,
                fromStatus: null,
                toStatus: 'pending',
                changedBy: $creator->id,
                note: null
            );

            return $complaint->load(['category', 'department']);
        });
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
        // ممكن مستقبلاً نفلتر الإدارات/التصنيفات بناءً على صلاحيات المستخدم
        $departments = $this->departments->allActive();
        $categories  = $this->categories->allActive();

        // $priorities = [
        //     [
        //         'value' => 'low',
        //         'label' => __('complaints.priority.low'),
        //     ],
        //     [
        //         'value' => 'medium',
        //         'label' => __('complaints.priority.medium'),
        //     ],
        //     [
        //         'value' => 'high',
        //         'label' => __('complaints.priority.high'),
        //     ],
        //     [
        //         'value' => 'urgent',
        //         'label' => __('complaints.priority.urgent'),
        //     ],
        // ];

        return [
            'departments' => $departments,
            'categories'  => $categories,
            // 'priorities'  => $priorities,
        ];
    }
}
