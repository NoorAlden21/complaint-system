<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command('backup:run')
    ->dailyAt('02:00');     // نسخة احتياطية يومية الساعة 2 الفجر

Schedule::command('backup:clean')
    ->dailyAt('03:00');     // تنظيف النسخ القديمة

Schedule::command('backup:monitor')
    ->dailyAt('09:00');     // مراقبة صحة آخر النسخ (اختياري)