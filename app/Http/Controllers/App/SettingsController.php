<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function profile()
    {
        return view('app.settings.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'bar_enrolment_no' => ['nullable', 'string', 'max:50'],
            'chamber_address' => ['nullable', 'string', 'max:1000'],
            'signature_block_en' => ['nullable', 'string', 'max:2000'],
            'signature_block_hi' => ['nullable', 'string', 'max:2000'],
        ]);
        auth()->user()->update($data);
        return back()->with('edit_success', 'Profile updated.');
    }

    public function telegram(TelegramClient $client)
    {
        $user = auth()->user();
        return view('app.settings.telegram', [
            'user' => $user,
            'botUsername' => $client->botUsername() ?: 'AarambhaxBot',
            'isConfigured' => $client->isConfigured(),
        ]);
    }

    public function generatePairingCode(Request $request)
    {
        $user = auth()->user();
        $user->update(['telegram_pairing_code' => Str::upper(Str::random(8))]);
        return back()->with('edit_success', 'Pairing code generated. Open the bot and send /start <code>.');
    }

    public function disconnectTelegram(Request $request)
    {
        auth()->user()->update([
            'telegram_chat_id' => null,
            'telegram_pairing_code' => null,
        ]);
        return back()->with('edit_success', 'Telegram disconnected.');
    }

    public function toggleAlerts(Request $request)
    {
        $user = auth()->user();
        $user->update(['telegram_alerts_enabled' => ! $user->telegram_alerts_enabled]);
        return back()->with('edit_success', $user->telegram_alerts_enabled ? 'Alerts enabled.' : 'Alerts paused.');
    }
}
