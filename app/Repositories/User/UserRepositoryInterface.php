<?php

namespace App\Repositories\User;

use App\Models\User;

interface UserRepositoryInterface
{
    public function paginate();

    public function findById(int $id);

    public function create(array $attributes): User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): User;

    public function createEmployee(array $data): User;

    public function getAllEmployees();

    public function findEmployeeById(int $id): ?User;

    public function updateEmployee(int $id, array $data): ?User;

    public function deleteEmployee(int $id): void;
}
