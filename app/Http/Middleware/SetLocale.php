<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected array $supportedLocales = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Accept-Language');
        $locale = null;

        if ($header) {
            $parts = explode(',', $header);

            foreach ($parts as $part) {
                $part = trim($part);              // "ar-SA" أو "ar;q=0.9"
                $code = explode(';', $part)[0];   // "ar-SA"
                $short = substr($code, 0, 2);     // "ar"

                if (in_array($short, $this->supportedLocales, true)) {
                    $locale = $short;
                    break;
                }
            }
        }

        if (!$locale) {
            $locale = config('app.locale'); // fallback
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
