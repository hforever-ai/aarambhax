<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Concerns\DetectsBotRequests;
use App\Http\Controllers\Controller;
use App\Mail\NewUserPendingApproval;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use DetectsBotRequests;

    public function showLogin()
    {
        if (auth()->check()) {
            return redirect()->route('app.dashboard');
        }
        return view('app.auth.login');
    }

    public function login(Request $request)
    {
        // Header-based bot check — kills script-kiddie automation before
        // any DB hit. Real browsers always pass; curl/python without spoofed
        // headers fails here.
        if ($this->looksLikeBot($request)) {
            return $this->botRejectResponse($request);
        }

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($data, true)) {
            $request->session()->regenerate();
            return redirect()->intended(route('app.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->withInput($request->only('email'));
    }

    public function showRegister()
    {
        if (auth()->check()) {
            return redirect()->route('app.dashboard');
        }
        return view('app.auth.register');
    }

    public function register(Request $request)
    {
        // Layer 1 — header-based bot check. Kills curl / python / wget / etc.
        if ($this->looksLikeBot($request)) {
            return $this->botRejectResponse($request);
        }

        // Layer 2 — honeypot. Field 'website' is hidden in the form;
        // legitimate users never type into it. Bots blindly fill all fields.
        if (! empty($request->input('website'))) {
            // Don't tell the bot anything — pretend the registration "worked"
            // so they don't probe further. Just don't actually create an account.
            return redirect()->route('login')->with('flash', 'Registration received.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // New users are PENDING by default — admin must approve before any
        // write actions are unlocked. Status default is set by the DB column.
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => User::STATUS_PENDING,
        ]);

        // Notify admins by email so they can approve/reject promptly. Failure to
        // send must NOT break the registration flow — log and continue.
        try {
            $adminEmails = collect(explode(',', (string) config('services.aarambh_app.admin_notify_emails', '')))
                ->map(fn ($e) => trim($e))
                ->filter()
                ->values()
                ->all();
            if (! empty($adminEmails)) {
                Mail::to($adminEmails)->send(new NewUserPendingApproval($user));
            }
        } catch (\Throwable $e) {
            Log::warning('register: admin notification email failed', ['err' => $e->getMessage()]);
        }

        // Auto-login so user lands on dashboard with the pending banner. They
        // CAN log in and browse — they just can't do anything until approved.
        Auth::login($user);

        return redirect()
            ->route('app.dashboard')
            ->with('flash', 'Registration received! Your account is pending admin approval. You\'ll be notified once approved.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
