<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function set(Request $request, string $locale)
    {
        if (! in_array($locale, ['en', 'hi'], true)) {
            abort(404);
        }
        $request->session()->put('locale', $locale);
        return back();
    }
}
