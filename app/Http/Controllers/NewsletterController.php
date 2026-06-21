<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscriber::firstOrCreate(
            ['email' => $data['email']],
            ['source' => 'home_waitlist']
        );

        return redirect()->back()->with('newsletter_success', true);
    }

    public function unsubscribe(string $token)
    {
        $sub = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();
        $sub->delete();
        return view('pages.unsubscribed');
    }
}
