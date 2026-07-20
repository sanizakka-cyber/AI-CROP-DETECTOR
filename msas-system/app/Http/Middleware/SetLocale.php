<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Priority: explicit session choice > user's saved DB preference > app default
        $locale = session('locale');
        if (!$locale && auth()->check()) {
            $locale = auth()->user()->language;
            if ($locale) session(['locale' => $locale]); // warm the session so future requests skip the DB hit
        }
        $locale = $locale ?: config('app.locale', 'en');

        $allowed = ['en', 'ha', 'yo', 'ig', 'fr'];
        if (!in_array($locale, $allowed)) $locale = 'en';

        app()->setLocale($locale);
        return $next($request);
    }
}
