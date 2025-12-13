<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function paginate()
    {
        return User::role('citizen')
            ->select(['id', 'name', 'email', 'phone_number', 'created_at'])
            ->withCount('complaints')
            ->paginate(15);
    }

    public function findById(int $id)
    {
        return User::query()
            ->select(['id', 'name', 'email', 'phone_number', 'created_at'])
            ->with([
                'complaints' => function ($q) {
                    $q->latest()
                        ->select([
                            'id', 'reference_number', 'title', 'status', 'priority',
                            'category_id', 'department_id', 'region_id',
                            'created_by', 'created_at', 'updated_at'
                        ])
                        ->with(['category', 'department', 'region'])
                        ->withCount(['attachments', 'notes', 'versions'])
                        ->limit(15);
                },
            ])
            ->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $attributes): User
    {
        $attributes['password'] = Hash::make($attributes['password']);

        return User::create($attributes);
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
