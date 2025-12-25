<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EmployeesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        $departments = Department::all();

        if ($departments->isEmpty()) {
            Log::channel('employee_tokens')->warning('No departments found. EmployeesDemoSeeder did not create any employees.');
            return;
        }

        foreach ($departments as $department) {
            // عدد الموظفين لكل قسم (تقدر تعدله)
            $employeesCount = 2;

            for ($i = 1; $i <= $employeesCount; $i++) {
                // إنشاء المستخدم (بدون department_id في create عشان الـ fillable)
                $user = User::create([
                    'name'         => $faker->name(),
                    'phone_number' => str_pad((string) random_int(500000000, 599999999), 10, '0', STR_PAD_LEFT),
                    'email'        => "employee_{$department->id}_{$i}@example.com",
                    'password'     => Hash::make('password'), // كلمة المرور الافتراضية: password
                ]);

                // ربطه بالقسم
                $user->department_id = $department->id;
                $user->save();

                // تعيين دور employee (من Spatie)
                $user->assignRole('employee');

                // إنشاء توكن للموظف
                $token = $user->createToken('employee_token')->plainTextToken;

                // تسجيل التوكن في ملف لوج خاص بالموظفين
                Log::channel('employee_tokens')->info(sprintf(
                    'Employee token | name: %s | email: %s| token: %s',
                    $user->name,
                    $user->email,
                    $token
                ));
            }
        }

        Log::channel('employee_tokens')->info('============================================================');
    }
}
