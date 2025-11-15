<?php

namespace Database\Seeders;

use App\Models\ComplaintCategory;
use Illuminate\Database\Seeder;

class ComplaintCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'label_ar'       => 'تأخير في تقديم الخدمة',
                'label_en'       => 'Service Delay',
                'description_ar' => 'شكاوى تتعلق بتأخر إنجاز الطلبات أو الخدمات.',
                'description_en' => 'Complaints related to delayed processing of services or requests.',
                'is_active'      => true,
            ],
            [
                'label_ar'       => 'مشكلة تقنية',
                'label_en'       => 'Technical Issue',
                'description_ar' => 'أعطال في الأنظمة أو المنصات الإلكترونية أو التطبيقات.',
                'description_en' => 'Issues with systems, portals or applications.',
                'is_active'      => true,
            ],
            [
                'label_ar'       => 'مشكلة مالية / فاتورة',
                'label_en'       => 'Billing / Financial Issue',
                'description_ar' => 'شكاوى حول الفواتير، الرسوم، أو المدفوعات.',
                'description_en' => 'Complaints about bills, fees or payments.',
                'is_active'      => true,
            ],
            [
                'label_ar'       => 'سلوك موظف',
                'label_en'       => 'Staff Behavior',
                'description_ar' => 'شكاوى تتعلق بتعامل أو سلوك الموظفين.',
                'description_en' => 'Complaints about staff behavior or attitude.',
                'is_active'      => true,
            ],
            [
                'label_ar'       => 'محتوى أو معلومات غير صحيحة',
                'label_en'       => 'Incorrect Information',
                'description_ar' => 'وجود بيانات أو معلومات خاطئة في النظام أو الموقع.',
                'description_en' => 'Incorrect or misleading information in systems or website.',
                'is_active'      => true,
            ],
            [
                'label_ar'       => 'أخرى',
                'label_en'       => 'Other',
                'description_ar' => 'شكاوى لا تندرج تحت التصنيفات الأخرى.',
                'description_en' => 'Complaints that do not fit other categories.',
                'is_active'      => true,
            ],
        ];

        foreach ($categories as $data) {
            ComplaintCategory::firstOrCreate(
                ['label_en' => $data['label_en']],
                $data
            );
        }
    }
}
