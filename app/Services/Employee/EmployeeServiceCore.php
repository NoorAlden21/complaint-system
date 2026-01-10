<?php

namespace App\Services\Employee;

use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final class EmployeeServiceCore
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function getAllEmployees()
    {
        return $this->userRepository->getAllEmployees();
    }

    public function createEmployeeDb(array $data): User
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

    public function updateEmployeeDb(int $id, array $data): ?User
    {
        return $this->userRepository->updateEmployee($id, $data);
    }

    public function deleteEmployeeDb(int $id): void
    {
        $this->userRepository->deleteEmployee($id);
    }
}
