<?php

namespace App\Http\Controllers\App;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Models\CaseRecord;
use App\Models\Conversation;
use App\Services\AarambhAi\ConversationOrchestrator;
use App\Services\AarambhAi\ConvertChatToDraft;
use App\Services\Quota\UserApiQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConversationController extends Controller
{
    /**
     * Legacy /chat route — redirect to the Brainstorm tab where chat now lives.
     */
    public function show(CaseRecord $case)
    {
        $this->authorise($case);
        return redirect()->route('app.cases.tab.brainstorm', $case);
    }

    /**
     * Send a chat message. Used by both the legacy form (full page POST,
     * redirects back) and the new fetch-based composer on the Brainstorm
     * tab (returns JSON with the new turn).
     *
     * Synchronous: ConversationOrchestrator::sendMessage hits Gemini Flash
     * Lite (~2-3s), well within Hostinger's 120s LiteSpeed cap.
     */
    public function send(Request $request, CaseRecord $case)
    {
        $this->authorise($case);
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['integer'],
            'kind' => ['nullable', 'string', 'in:brainstorm,bahas,cross_exam,samjhao,analysis,draft,notes'],
            'output_language' => ['nullable', 'string', 'in:en,hi,hinglish,bilingual'],
            'model_override' => ['nullable', 'string', 'in:gemini-flash-lite,gemini-flash,deepseek-flash,deepseek-pro'],
        ]);

        // Kind comes from the calling tab (query string from JS or
        // URL path on form post). Defaults to brainstorm to preserve the
        // legacy single-thread behavior.
        $kind = $data['kind'] ?? $request->query('kind', \App\Models\Conversation::KIND_BRAINSTORM);

        $orch = ConversationOrchestrator::make();
        $conversation = $orch->getOrCreate($case, $request->user(), $kind);

        // Defense-in-depth: only allow doc IDs that actually belong to this case
        $allowedDocIds = [];
        if (! empty($data['document_ids'])) {
            $allowedDocIds = $case->documents()
                ->whereIn('id', $data['document_ids'])
                ->whereNotNull('ocr_text')
                ->pluck('id')
                ->all();
        }

        try {
            UserApiQuota::fromConfig()->checkAndConsume($request->user(), 'chat', 1, $conversation);
        } catch (QuotaExceededException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'quota', 'message' => $e->getMessage()], 429);
            }
            return back()->withErrors(['quota' => $e->getMessage()])->withInput();
        }

        try {
            $result = $orch->sendMessage(
                $conversation,
                $data['message'],
                $allowedDocIds,
                $data['output_language'] ?? null,
                $data['model_override'] ?? null,
            );
        } catch (Throwable $e) {
            Log::error('chat send failed', ['err' => $e->getMessage(), 'case' => $case->id]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'send_failed', 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['message' => 'Chat failed: '.$e->getMessage()])->withInput();
        }

        // JSON response for the inline fetch-based composer
        if ($request->expectsJson() || $request->wantsJson()) {
            $userMsg = $result['user_msg'];
            $assistantMsg = $result['assistant_msg'];
            $payload = [
                'user_message' => [
                    'id' => $userMsg->id,
                    'role' => 'user',
                    'content' => $userMsg->content,
                    'created_at' => $userMsg->created_at->toIso8601String(),
                ],
                'assistant_message' => [
                    'id' => $assistantMsg->id,
                    'role' => 'assistant',
                    'content' => $assistantMsg->content,
                    'content_html' => $this->renderMessageHtml($assistantMsg->content),
                    'model_used' => $assistantMsg->model_used,
                    'tier' => str_contains((string) $assistantMsg->model_used, 'lite') ? 'free' : 'paid',
                    'tokens_in' => $assistantMsg->tokens_input,
                    'tokens_out' => $assistantMsg->tokens_output,
                    'created_at' => $assistantMsg->created_at->toIso8601String(),
                ],
            ];
            // Admins see the full prompts sent to the LLM for review and improvement
            if ($request->user()->is_admin) {
                $payload['debug_prompts'] = [
                    'system' => $result['sys_prompt'] ?? '',
                    'user'   => $result['user_prompt'] ?? '',
                ];
            }
            return response()->json($payload);
        }

        return redirect()->route('app.cases.tab.brainstorm', $case);
    }

    public function convertToDraft(Request $request, CaseRecord $case)
    {
        $this->authorise($case);
        $conversation = ConversationOrchestrator::make()->getOrCreate($case, $request->user());

        if ($conversation->messages()->count() < 2) {
            return back()->withErrors(['convert' => 'Need at least one back-and-forth before converting to draft.']);
        }

        $data = $request->validate([
            'forum' => ['nullable', 'string', 'max:50'],
            'draft_type' => ['nullable', 'string', 'max:200'],
            'language' => ['nullable', 'in:en,hi,bilingual'],
        ]);

        try {
            $draft = ConvertChatToDraft::make()->fromConversation(
                conversation: $conversation,
                user: $request->user(),
                forumOverride: $data['forum'] ?? null,
                draftTypeOverride: $data['draft_type'] ?? null,
                languageOverride: $data['language'] ?? null,
            );
        } catch (Throwable $e) {
            Log::error('chat-convert-to-draft', ['err' => $e->getMessage()]);
            return back()->withErrors(['convert' => 'Convert failed: '.$e->getMessage()]);
        }

        return redirect()->route('app.drafts.show', $draft->id)
            ->with('edit_success', 'Draft generated from chat. Review the [TO BE FILLED] markers before filing.');
    }

    /**
     * Add to Draft — cross-tab handoff. Take any assistant message from any
     * chat thread on this case, copy its content into the Draft thread as
     * a user message (seed for the next draft turn), redirect to Draft tab.
     *
     * Stays free of mutations to the source message. We never edit history;
     * just plant a seed in a new thread.
     */
    public function copyToDraft(Request $request, CaseRecord $case, \App\Models\ConversationMessage $message)
    {
        $this->authorise($case);

        // Source message must belong to a conversation on THIS case
        $srcConv = $message->conversation;
        if (! $srcConv || (int) $srcConv->case_id !== (int) $case->id) {
            abort(404);
        }
        // Only assistant turns can be added to draft (user turns are just questions)
        if ($message->role !== 'assistant') {
            return back()->withErrors(['copy' => 'Only Aarambh\'s answers can be added to draft.']);
        }

        $orch = ConversationOrchestrator::make();
        $draftConv = $orch->getOrCreate($case, $request->user(), \App\Models\Conversation::KIND_DRAFT);

        $sourceLabel = ucfirst(str_replace('_', ' ', $srcConv->kind ?? 'brainstorm'));
        \App\Models\ConversationMessage::create([
            'conversation_id' => $draftConv->id,
            'role' => 'user',
            'content' => "[Carried over from {$sourceLabel}]\n\n".$message->content,
            'created_at' => now(),
        ]);
        $draftConv->touch();

        return redirect()->route('app.cases.tab.draft', $case)
            ->with('flash', "Added to Draft from {$sourceLabel}. Edit and run from the composer below.");
    }

    public function copyToNotes(Request $request, CaseRecord $case, \App\Models\ConversationMessage $message)
    {
        $this->authorise($case);

        $srcConv = $message->conversation;
        if (! $srcConv || (int) $srcConv->case_id !== (int) $case->id) {
            abort(404);
        }
        if ($message->role !== 'assistant') {
            return back()->withErrors(['copy' => 'Only Aarambh\'s answers can be added to notes.']);
        }

        $orch = ConversationOrchestrator::make();
        $notesConv = $orch->getOrCreate($case, $request->user(), \App\Models\Conversation::KIND_NOTES);

        $sourceLabel = ucfirst(str_replace('_', ' ', $srcConv->kind ?? 'brainstorm'));
        \App\Models\ConversationMessage::create([
            'conversation_id' => $notesConv->id,
            'role' => 'user',
            'content' => "[Saved from {$sourceLabel}]\n\n".$message->content,
            'created_at' => now(),
        ]);
        $notesConv->touch();

        return redirect()->route('app.cases.tab.notes', $case)
            ->with('flash', "Saved to Notes from {$sourceLabel}.");
    }

    /**
     * Render an assistant message to HTML. If the message is bilingual
     * (contains <<<ENGLISH>>> + <<<HINDI>>> delimiters), produce a
     * side-by-side div pair instead of plain markdown.
     */
    private function renderMessageHtml(?string $content): string
    {
        $content = (string) ($content ?? '');
        if (str_contains($content, '<<<ENGLISH>>>') && str_contains($content, '<<<HINDI>>>')) {
            [, $rest] = explode('<<<ENGLISH>>>', $content, 2);
            [$enPart, $hiPart] = explode('<<<HINDI>>>', $rest, 2);
            $enHtml = \Illuminate\Support\Str::of(trim($enPart))->markdown(['html_input' => 'escape']);
            $hiHtml = \Illuminate\Support\Str::of(trim($hiPart))->markdown(['html_input' => 'escape']);
            return '<div class="bs-bilingual">'
                . '<div class="bs-bilingual-col"><div class="bs-bilingual-label">English</div><div>'.$enHtml.'</div></div>'
                . '<div class="bs-bilingual-col"><div class="bs-bilingual-label">हिन्दी</div><div>'.$hiHtml.'</div></div>'
                . '</div>';
        }
        return (string) \Illuminate\Support\Str::of($content)->markdown(['html_input' => 'escape']);
    }

    private function authorise(CaseRecord $case): void
    {
        abort_if((int) $case->user_id !== (int) auth()->id(), 403);
    }
}
