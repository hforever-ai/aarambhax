<?php

namespace Tests\Feature;

use App\Models\CaseRecord;
use App\Models\Hearing;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_command_runs_clean_with_no_hearings(): void
    {
        $this->artisan('aarambhax:send-hearing-reminders')->assertExitCode(0);
    }

    public function test_reminder_command_marks_hearings_as_reminded(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'telegram_alerts_enabled' => true,
        ]);
        $case = CaseRecord::create([
            'user_id' => $user->id,
            'title' => 'Test', 'forum' => 'cg_district', 'category' => 'criminal',
            'status' => 'active',
        ]);
        $hearing = Hearing::create([
            'user_id' => $user->id,
            'case_id' => $case->id,
            'date' => today(),
            'purpose' => 'Test purpose',
        ]);

        // TelegramClient with no token will stub-success
        $this->artisan('aarambhax:send-hearing-reminders')->assertExitCode(0);

        $this->assertNotNull($hearing->fresh()->reminded_at);
    }

    public function test_telegram_client_is_stub_safe_without_token(): void
    {
        $client = new TelegramClient(token: null);
        $this->assertFalse($client->isConfigured());
        $this->assertTrue($client->sendMessage('123', 'hello')); // stub returns true
    }
}
