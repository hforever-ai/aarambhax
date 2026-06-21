<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = ['en', 'hi'];
        $locale = $request->session()->get('locale');
        if (! in_array($locale, $supported, true)) {
            $locale = 'en';
        }
        app()->setLocale($locale);
        return $next($request);
    }
}
