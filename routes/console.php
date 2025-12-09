<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\RunBackupJob;
use App\Jobs\ReleaseExpiredComplaintLocksJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::job(new RunBackupJob)
    ->dailyAt('02:00');

Schedule::job(new ReleaseExpiredComplaintLocksJob)
    ->everyFiveMinutes();


Schedule::command('backup:clean')
    ->dailyAt('03:00');     // تنظيف النسخ القديمة

Schedule::command('backup:monitor')
    ->dailyAt('09:00');     // مراقبة صحة آخر النسخ (اختياري)