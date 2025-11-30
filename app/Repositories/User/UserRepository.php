<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        $attributes['password'] = Hash::make($attributes['password']);

        return User::create($attributes);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function save(User $user): User
    {
        $user->save();

        return $user;
    }

    public function createEmployee(array $data): User
    {
        $user = User::create($data);

        return $user;
    }
}
