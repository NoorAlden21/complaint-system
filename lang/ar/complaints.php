<?php

return [
    'status' => [
        'pending'          => 'قيد المراجعة',
        'needs_more_info'  => 'بانتظار معلومات إضافية',
        'open'             => 'مفتوحة',
        'in_progress'      => 'تحت المعالجة',
        'resolved'         => 'تم الحل',
        'closed'           => 'مغلقة',
        'rejected'         => 'مرفوضة',
    ],

    'priority' => [
        'low'     => 'منخفضة',
        'medium'  => 'متوسطة',
        'high'    => 'عالية',
        'urgent'  => 'عاجلة',
    ],

    'locked_by_other' => 'هذه الشكوى مقفولة حاليًا من مستخدم آخر.',
    'optimistic_lock_conflict' => 'تم تعديل هذه الشكوى من قبل مستخدم آخر. يرجى تحديث الصفحة والمحاولة مرة أخرى.',

    'reply_only_when_needs_more_info' => 'لا يمكنك الرد إلا عندما تكون حالة الشكوى "بحاجة لمعلومات إضافية".',

    'version_notes' => [
        'info_reply' => 'رد المستخدم على طلب معلومات.',
    ],
];
