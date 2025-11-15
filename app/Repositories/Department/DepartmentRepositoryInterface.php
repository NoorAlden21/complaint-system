<?php

namespace App\Repositories\Department;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function allActive(): Collection;
}
