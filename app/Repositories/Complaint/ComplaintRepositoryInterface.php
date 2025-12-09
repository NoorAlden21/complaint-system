<?php

namespace App\Repositories\Complaint;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComplaintRepositoryInterface
{
    public function findById(int $id): ?Complaint;

    public function createForUser(User $user, array $attributes): Complaint;

    public function findForUser(User $user, int $id): ?Complaint;

    public function paginateFor(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;

    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;

    public function paginateForEmployee(
        User $user,
        array $filters = [],
        int $perPage
    ): LengthAwarePaginator;

    public function paginateForUser(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;
}
