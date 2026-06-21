<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::published()->lang('en')->orderBy('topic')->orderBy('display_order')->get();

        return view('pages.faq', compact('faqs'));
    }
}
