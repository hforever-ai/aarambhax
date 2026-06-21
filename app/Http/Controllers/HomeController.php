<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class HomeController extends Controller
{
    public function index()
    {
        $faqs = Faq::published()->lang('en')->featured()->limit(6)->get();

        return view('pages.home', [
            'faqs' => $faqs,
        ]);
    }
}
