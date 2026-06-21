<?php

namespace Tests\Feature;

use App\Models\Draft;
use App\Models\User;
use App\Services\DraftEditor\EditOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authed_user_can_create_draft(): void
    {
        $user = User::factory()->create();

        $r = $this->actingAs($user)->post('/app/drafts', [
            'title' => 'Test bail',
            'forum' => 'cg_district',
            'category' => 'criminal',
            'draft_type' => 'anticipatory_bail',
            'language' => 'hi',
            'parties' => 'Ram Verma vs State',
            'key_facts' => 'Resident of Bilaspur, cooperating with investigation.',
        ]);

        $r->assertRedirect();
        $this->assertDatabaseHas('drafts', [
            'user_id' => $user->id,
            'title' => 'Test bail',
            'forum' => 'cg_district',
        ]);
    }

    public function test_orchestrator_records_messages_and_snapshots_on_initial_generation(): void
    {
        $user = User::factory()->create();
        $draft = Draft::create([
            'user_id' => $user->id,
            'title' => 'X', 'forum' => 'cg_district', 'category' => 'criminal',
            'draft_type' => 'bail', 'language' => 'en',
            'context_facts' => ['parties' => 'A vs B'],
            'context_legal' => [],
            'context_user_prefs' => [],
            'current_content_md' => '',
            'status' => 'drafting',
        ]);

        EditOrchestrator::make()->generateInitial($draft);

        $draft->refresh();
        $this->assertSame('editing', $draft->status);
        $this->assertGreaterThan(0, $draft->messages()->count());
        $this->assertGreaterThan(0, $draft->snapshots()->count());
    }

    public function test_apply_edit_preserves_full_message_history(): void
    {
        $user = User::factory()->create();
        $draft = Draft::create([
            'user_id' => $user->id,
            'title' => 'X', 'forum' => 'cg_district', 'category' => 'criminal',
            'draft_type' => 'bail', 'language' => 'en',
            'context_facts' => ['parties' => 'A vs B'],
            'context_legal' => [],
            'context_user_prefs' => [],
            'current_content_md' => 'Initial body',
            'status' => 'editing',
        ]);

        $orch = EditOrchestrator::make();
        $orch->applyEdit($draft, [
            'intent' => 'free_form',
            'instruction' => 'add a ground about delay',
            'selection_text' => null,
            'selection_start' => null,
            'selection_end' => null,
        ]);

        $draft->refresh();
        // user message + assistant message = 2
        $this->assertGreaterThanOrEqual(2, $draft->messages()->count());

        // Apply a second edit — verify history grows, not replaces
        $orch->applyEdit($draft, [
            'intent' => 'tighten',
            'instruction' => 'tighten',
            'selection_text' => null,
            'selection_start' => null,
            'selection_end' => null,
        ]);
        $this->assertGreaterThanOrEqual(4, $draft->fresh()->messages()->count());
    }

    public function test_user_cannot_view_other_users_draft(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $draft = Draft::create([
            'user_id' => $owner->id,
            'title' => 'Mine', 'forum' => 'cg_district', 'category' => 'criminal',
            'draft_type' => 'bail', 'language' => 'en',
            'context_facts' => [], 'context_legal' => [], 'context_user_prefs' => [],
            'current_content_md' => 'x', 'status' => 'drafting',
        ]);

        $this->actingAs($other)->get("/app/drafts/{$draft->id}")->assertStatus(403);
    }
}
