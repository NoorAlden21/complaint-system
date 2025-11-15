<?php

namespace App\Repositories\Department;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function allActive(): Collection
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }
}
