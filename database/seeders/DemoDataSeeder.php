<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintCategory;
use App\Models\Department;
use App\Models\Region;
use App\Models\ComplaintVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // 1) تجهيز بيانات أساسية لو ناقصة (categories / departments / regions)
        if (ComplaintCategory::count() === 0) {
            ComplaintCategory::create([
                'label_ar'       => 'خدمات كهرباء',
                'label_en'       => 'Electricity Services',
                'description_ar' => 'شكاوى تتعلق بخدمات الكهرباء',
                'description_en' => 'Complaints related to electricity services',
                'is_active'      => true,
            ]);

            ComplaintCategory::create([
                'label_ar'       => 'خدمات مياه',
                'label_en'       => 'Water Services',
                'description_ar' => 'شكاوى تتعلق بخدمات المياه',
                'description_en' => 'Complaints related to water services',
                'is_active'      => true,
            ]);
        }

        if (Department::count() === 0) {
            Department::create([
                'name_ar'        => 'خدمة العملاء',
                'name_en'        => 'Customer Service',
                'description_ar' => 'قسم خدمة العملاء',
                'description_en' => 'Customer service department',
                'is_active'      => true,
            ]);

            Department::create([
                'name_ar'        => 'الدعم الفني',
                'name_en'        => 'Technical Support',
                'description_ar' => 'قسم الدعم الفني',
                'description_en' => 'Technical support department',
                'is_active'      => true,
            ]);
        }

        if (Region::count() === 0) {
            Region::create([
                'name_ar' => 'الرياض',
                'name_en' => 'Riyadh',
            ]);

            Region::create([
                'name_ar' => 'جدة',
                'name_en' => 'Jeddah',
            ]);
        }

        $categories  = ComplaintCategory::all();
        $departments = Department::all();
        $regions     = Region::all();

        // 2) إنشاء مجموعة من المستخدمين
        $users = collect();

        for ($i = 1; $i <= 5; $i++) {
            $user = User::create([
                'name'         => $faker->name(),
                'phone_number' => str_pad((string) random_int(500000000, 599999999), 10, '0', STR_PAD_LEFT),
                'email'        => "citizen{$i}@example.com",
                'password'     => Hash::make('password'), // كلمة المرور الافتراضية: password
            ]);

            // تعيين دور المواطن
            $user->assignRole('citizen');

            // إنشاء توكن للمواطن
            $token = $user->createToken('citizen_token')->plainTextToken;

            // تسجيل التوكن في ملف users tokens
            Log::channel('user_tokens')->info(sprintf(
                'Citizen token | name: %s | email: %s | token: %s',
                $user->name,
                $user->email,
                $token
            ));

            $users->push($user);
        }
        // 3) لكل مستخدم ننشئ شكوى أو شكوتين، وبعضها لها مرفقات
        foreach ($users as $user) {
            $complaintCount = random_int(1, 2);

            for ($c = 1; $c <= $complaintCount; $c++) {
                $category   = $categories->random();
                $department = $departments->random();
                $region     = $regions->random();

                $complaint = Complaint::create([
                    'reference_number'  => null, // نولّدها بعد الإنشاء
                    'title'             => $faker->sentence(4),
                    'description'       => $faker->paragraph(),
                    'status'            => 'pending',
                    'priority'          => 'medium',
                    'category_id'       => $category->id,
                    'department_id'     => $department->id,
                    'region_id'         => $region->id,
                    'created_by'        => $user->id,
                    'sla_due_at'        => now()->addDays(5),
                    'resolved_at'       => null,
                    'closed_at'         => null,
                    'resolution_summary' => null,
                ]);

                // توليد reference_number حسب نفس الفكرة اللي تستخدمها في الخدمة
                $complaint->reference_number = 'CMP-' . now()->format('Y') . '-' . str_pad($complaint->id, 6, '0', STR_PAD_LEFT);
                $complaint->save();

                // إنشاء نسخة أولى من الشكوى (version 1) عشان تتماشى مع تصميمك
                ComplaintVersion::create([
                    'complaint_id'  => $complaint->id,
                    'version_number' => 1,
                    'title'         => $complaint->title,
                    'description'   => $complaint->description,
                    'status'        => $complaint->status,
                    'priority'      => $complaint->priority,
                    'category_id'   => $complaint->category_id,
                    'department_id' => $complaint->department_id,
                    'region_id'     => $complaint->region_id,
                    'changed_by'    => $user->id,
                    'note'          => 'النسخة الأولى من الشكوى (تجريبية من seeder).',
                ]);

                // 4) لبعض الشكاوى فقط نضيف مرفقات
                if (random_int(0, 1) === 1) {
                    $attachmentsCount = random_int(1, 3);

                    for ($a = 1; $a <= $attachmentsCount; $a++) {
                        $fakeFileName = "attachment_{$complaint->id}_{$a}.pdf";

                        ComplaintAttachment::create([
                            'complaint_id'     => $complaint->id,
                            'uploaded_by'      => $user->id,
                            'original_name'    => $fakeFileName,
                            'path'             => "complaints/demo/{$complaint->id}/{$fakeFileName}",
                            'mime_type'        => 'application/pdf',
                            'size'             => random_int(10_000, 500_000), // بالحجم بالبايت
                            'added_in_version' => 1,
                            'removed_in_version' => null,
                        ]);
                    }
                }
            }
        }
    }
}
