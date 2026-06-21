<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Karya (कार्य) — typed actions an advocate runs on a Case's document set.
 *
 * Separate from CaseAnalysis / Conversation / Draft. Same async pipeline
 * pattern as those: queued → running → done | failed; live progress.
 *
 * Each Karya knows which Documents it consumed (input_document_ids) — the
 * provenance / malpractice shield Gemini called out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('case_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type', 60)->index();           // case_brief | timeline | reply_to_notice | argument_synopsis | samjhao | etc.
            $table->string('title', 500);
            $table->string('language', 12)->default('en'); // en | hi | hinglish | bilingual

            // Provenance — array of Document IDs this Karya consumed
            $table->json('input_document_ids');

            // Per-type config (e.g. samjhao target, reply tone, etc.)
            $table->json('parameters')->nullable();

            // Lifecycle (mirrors case_analyses pattern, async via existing cron)
            $table->string('pipeline_status', 20)->default('queued');  // queued | running | done | failed
            $table->string('pipeline_stage', 100)->nullable();
            $table->unsignedTinyInteger('pipeline_progress')->default(0);
            $table->timestamp('pipeline_started_at')->nullable();
            $table->timestamp('pipeline_finished_at')->nullable();
            $table->text('pipeline_error')->nullable();

            // The output (Markdown for display + JSON for structured/machine use)
            $table->longText('output_markdown')->nullable();
            $table->json('output_json')->nullable();

            // Cost / tokens
            $table->string('model_used', 100)->nullable();
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('cost_inr_paise')->default(0);

            $table->timestamps();

            $table->index(['case_id', 'type']);
            $table->index(['case_id', 'created_at'], 'karyas_case_created_idx');
        });

        Schema::create('karya_messages', function (Blueprint $table) {
            // For chat-sidebar refinement of a Karya artifact (same UX as Draft / Analysis edit chat).
            $table->id();
            $table->foreignId('karya_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->longText('content');
            $table->string('intent', 50)->nullable();
            $table->string('model_used', 100)->nullable();
            $table->unsignedInteger('tokens_input')->default(0);
            $table->unsignedInteger('tokens_output')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['karya_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karya_messages');
        Schema::dropIfExists('karyas');
    }
};
