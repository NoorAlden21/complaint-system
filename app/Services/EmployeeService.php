<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;

class EmployeeService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
    }

    public function createEmployee(array $data): User
    {
        return $this->userRepository->createEmployee($data);
    }
}
