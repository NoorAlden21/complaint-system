<?php

namespace App\Repositories\Complaint;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    public function createForUser(User $user, array $attributes): Complaint
    {
        $attributes['created_by'] = $user->id;

        return Complaint::create($attributes);
    }

    public function findForUser(User $user, int $id): ?Complaint
    {
        $query = Complaint::query()
            ->with(['category', 'department']);

        if ($user->hasRole('citizen')) {
            $query->where('created_by', $user->id);
        }

        // لاحقاً نقدر نضيف منطق officer / super_admin بشكل أدق

        return $query->find($id);
    }

    public function paginateForUser(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Complaint::query()
            ->with(['category', 'department'])
            ->latest('created_at');

        if ($user->hasRole('citizen')) {
            $query->where('created_by', $user->id);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        $perPage = min(max((int) $perPage, 1), 100);

        return $query->paginate($perPage);
    }
}
