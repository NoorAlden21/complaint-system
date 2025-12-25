<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
    }

    public function getAllEmployees()
    {
        return $this->userRepository->getAllEmployees();
    }

    public function createEmployee(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        $employee = $this->userRepository->createEmployee($data);
        $employee->assignRole('employee');
        return $employee;
    }

    public function getEmployeeById(int $id): ?User
    {
        return $this->userRepository->findEmployeeById($id);
    }

    public function updateEmployee(int $id, array $data): ?User
    {
        return $this->userRepository->updateEmployee($id, $data);
    }

    public function deleteEmployee(int $id): void
    {
        $this->userRepository->deleteEmployee($id);
    }
}
