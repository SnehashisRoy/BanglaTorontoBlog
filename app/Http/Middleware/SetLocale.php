<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['en', 'bn'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale', 'en');

        if (! in_array($locale, self::SUPPORTED, strict: true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        // Make route() helpers automatically include the current locale
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
