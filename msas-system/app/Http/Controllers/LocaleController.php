<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const ALLOWED = ['en', 'ha', 'yo', 'ig', 'ff'];

    public function set(Request $request)
    {
        $locale = $request->input('locale', 'en');
        if (!in_array($locale, self::ALLOWED)) $locale = 'en';

        session(['locale' => $locale]);
        app()->setLocale($locale);

        return back();
    }
}
