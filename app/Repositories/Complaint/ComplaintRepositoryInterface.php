<?php

namespace App\Repositories\Complaint;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComplaintRepositoryInterface
{
    public function createForUser(User $user, array $attributes): Complaint;

    public function findForUser(User $user, int $id): ?Complaint;

    public function paginateForUser(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;
}
