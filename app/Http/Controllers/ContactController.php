<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:200'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Contact::create($data);

        return redirect()->route('pages.contact')->with('contact_success', true);
    }
}
