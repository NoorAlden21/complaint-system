<?php

namespace App\Services\Employee;

use App\Models\User;
use App\Support\Aop\AopRunner;

final class EmployeeService
{
    public function __construct(
        private EmployeeServiceCore $core,
        private AopRunner $runner
    ) {
    }

    public function getAllEmployees()
    {
        return $this->runner->run(
            op: 'employee.list',
            fn: fn () => $this->core->getAllEmployees(),
            transactional: false
        );
    }

    public function createEmployee(array $data): User
    {
        return $this->runner->run(
            op: 'employee.create',
            fn: fn () => $this->core->createEmployeeDb($data),
            transactional: true
        );
    }

    public function getEmployeeById(int $id): ?User
    {
        return $this->runner->run(
            op: 'employee.show',
            fn: fn () => $this->core->getEmployeeById($id),
            transactional: false,
            context: ['employee_id' => $id]
        );
    }

    public function updateEmployee(int $id, array $data): ?User
    {
        return $this->runner->run(
            op: 'employee.update',
            fn: fn () => $this->core->updateEmployeeDb($id, $data),
            transactional: true,
            context: ['employee_id' => $id]
        );
    }

    public function deleteEmployee(int $id): void
    {
        $this->runner->run(
            op: 'employee.delete',
            fn: fn () => $this->core->deleteEmployeeDb($id),
            transactional: true,
            context: ['employee_id' => $id]
        );
    }
}
