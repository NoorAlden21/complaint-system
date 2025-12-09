<?php

namespace App\Repositories\Complaint;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    public function findById(int $id): ?Complaint
    {
        return Complaint::query()
            ->with(['category', 'department', 'region', 'attachments', 'versions.notes'])
            ->find($id);
    }

    public function createForUser(User $user, array $attributes): Complaint
    {
        $attributes['created_by'] = $user->id;

        return Complaint::create($attributes);
    }

    public function findForUser(User $user, int $id): ?Complaint
    {
        $query = Complaint::query()
            ->with(['category', 'department', 'region', 'attachments', 'versions.notes']);

        if ($user->hasRole('citizen')) {
            $query->where('created_by', $user->id);
        }

        return $query->find($id);
    }

    public function paginateFor(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        if ($user->hasRole('super_admin')) {
            return $this->paginate($filters, $perPage);
        }

        if ($user->hasRole('employee')) {
            return $this->paginateForEmployee($user, $filters, $perPage);
        }

        return $this->paginateForUser($user, $filters, $perPage);
    }

    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Complaint::query()->with(['category', 'department', 'region'])->latest('created_at');

        $this->applyFilters($query, $filters);

        $perPage = min(max((int) $perPage, 1), 100);
        return $query->paginate($perPage);
    }

    public function paginateForEmployee(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Complaint::query()->with(['category', 'department', 'region'])->latest('created_at');
        if ($user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        $this->applyFilters($query, $filters);

        $perPage = min(max((int) $perPage, 1), 100);
        return $query->paginate($perPage);
    }

    public function paginateForUser(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Complaint::query()
            ->with(['category', 'department', 'region'])
            ->latest('created_at')->where('created_by', $user->id);

        $this->applyFilters($query, $filters);

        $perPage = min(max((int) $perPage, 1), 100);

        return $query->paginate($perPage);
    }

    protected function applyFilters($query, array $filters): void
    {
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

        if (!empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }
    }
}
