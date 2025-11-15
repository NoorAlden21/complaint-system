<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $defaultGuard = config('auth.defaults.guard', 'web');

        Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => $defaultGuard,
        ]);

        Role::firstOrCreate([
            'name' => 'officer',
            'guard_name' => $defaultGuard,
        ]);

        Role::firstOrCreate([
            'name' => 'citizen',
            'guard_name' => $defaultGuard,
        ]);
    }
}
