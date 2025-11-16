<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Define the application's rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $key = strtolower($request->input('email')) ?: $request->ip();

            return [
                Limit::perMinute(5)->by($key),
            ];
        });

        RateLimiter::for('otp-send', function (Request $request) {
            $key = strtolower($request->input('email')) ?: $request->ip();

            return [
                Limit::perMinute(3)->by($key),
                Limit::perHour(10)->by($key . '|hour'),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $key = strtolower($request->input('email')) ?: $request->ip();

            return [
                Limit::perMinutes(15, 5)->by($key),
            ];
        });
    }
}
