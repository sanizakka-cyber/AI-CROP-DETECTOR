<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const ALLOWED = ['en', 'ha', 'yo', 'ig', 'fr', 'ff'];

    public function set(Request $request)
    {
        $locale = $request->input('locale', 'en');
        if (!in_array($locale, self::ALLOWED)) $locale = 'en';

        session(['locale' => $locale]);
        app()->setLocale($locale);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['locale' => $locale, 'success' => true]);
        }

        return back();
    }
}
