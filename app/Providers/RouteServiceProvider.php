<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    // limiter لمحاولات تسجيل الدخول
    RateLimiter::for('auth-login', function (Request $request) {
        $key = strtolower($request->input('email')) ?: $request->ip();

        return [
            // 5 محاولات في الدقيقة لكل إيميل/IP
            Limit::perMinute(5)->by($key),
        ];
    });

    // limiter لارسال OTP (تفعيل الإيميل + forgot password)
    RateLimiter::for('otp-send', function (Request $request) {
        $key = strtolower($request->input('email')) ?: $request->ip();

        return [
            // 3 مرات في الدقيقة
            Limit::perMinute(3)->by($key),
            // وبحد أقصى 10 مرات في الساعة لنفس الإيميل
            Limit::perHour(10)->by($key . '|hour'),
        ];
    });

    // limiter لمحاولة reset password بالـ OTP
    RateLimiter::for('otp-verify', function (Request $request) {
        $key = strtolower($request->input('email')) ?: $request->ip();

        return [
            // 5 محاولات في 15 دقيقة لنفس الإيميل
            Limit::perMinutes(15, 5)->by($key),
        ];
    });

    // لو عندك أشياء ثانية تبي تحدها...
}
