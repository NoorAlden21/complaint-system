<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $employeeRole   = Role::firstOrCreate(['name' => 'employee']);

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
                'phone_number' => '0921234123',
            ]
        );

        $superAdmin->assignRole($superAdminRole);

        $employee = User::firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name'     => 'Employee User',
                'password' => Hash::make('password'),
                'phone_number' => '0921234121',
            ]
        );

        $employee->assignRole($employeeRole);

        $superAdminToken = $superAdmin->createToken('super_admin_token')->plainTextToken;
        $employeeToken   = $employee->createToken('employee_token')->plainTextToken;

        Log::info('===== Seeded API Tokens =====');
        Log::info('Super Admin Token: ' . $superAdminToken);
        Log::info('Employee Token: ' . $employeeToken);
        Log::info('=============================');
    }
}
