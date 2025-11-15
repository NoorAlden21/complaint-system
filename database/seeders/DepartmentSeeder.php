<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            // [
            //     'name_ar'        => 'خدمة العملاء',
            //     'name_en'        => 'Customer Service',
            //     'description_ar' => 'استقبال الشكاوى والاستفسارات العامة من المستفيدين.',
            //     'description_en' => 'Handles general customer complaints and inquiries.',
            //     'is_active'      => true,
            // ],
            // [
            //     'name_ar'        => 'الدعم الفني',
            //     'name_en'        => 'Technical Support',
            //     'description_ar' => 'معالجة الأعطال والمشاكل التقنية في الأنظمة والخدمات.',
            //     'description_en' => 'Handles technical issues and system-related problems.',
            //     'is_active'      => true,
            // ],
            [
                'name_ar'        => 'الشؤون المالية',
                'name_en'        => 'Finance',
                'description_ar' => 'متابعة الشكاوى المتعلقة بالرسوم والمدفوعات والفواتير.',
                'description_en' => 'Handles complaints related to payments, fees and invoices.',
                'is_active'      => true,
            ],
            [
                'name_ar'        => 'الموارد البشرية',
                'name_en'        => 'Human Resources',
                'description_ar' => 'التعامل مع الشكاوى الخاصة بالموظفين وسياسات العمل.',
                'description_en' => 'Handles employee-related complaints and HR policies.',
                'is_active'      => true,
            ],
            [
                'name_ar'        => 'تقنية المعلومات',
                'name_en'        => 'IT & Infrastructure',
                'description_ar' => 'إدارة البنية التحتية التقنية وبلاغات الانقطاع.',
                'description_en' => 'Manages IT infrastructure and outage incidents.',
                'is_active'      => true,
            ],
        ];

        foreach ($departments as $data) {
            Department::firstOrCreate(
                ['name_en' => $data['name_en']],
                $data
            );
        }
    }
}
