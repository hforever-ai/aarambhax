<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Case-level strategic AI analyses. Schema mirrors `drafts` + `draft_messages`
 * + `draft_snapshots` so the existing chat-sidebar UI and EditOrchestrator
 * pattern reuse cleanly (~95% of code clonable).
 *
 * Distinct from drafts: an analysis is the AI's strategic read of the case
 * (issues, options, risks, gaps, recommended filing) — not a filable
 * pleading. Click "Convert to draft" to produce a real Draft from one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('case_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title', 500);
            $table->enum('language', ['en', 'hi', 'bilingual'])->default('en');
            $table->string('analysis_type', 50)->default('strategic'); // strategic | document_review | theory_exploration

            // Mirrors Draft's "living context"
            $table->json('context_facts');
            $table->json('context_legal')->nullable();
            $table->json('context_user_prefs')->nullable();

            $table->longText('current_content_md');               // analysis body itself
            $table->longText('current_content_html')->nullable();

            $table->enum('status', ['drafting', 'editing', 'finalised'])->default('drafting');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('case_id');
        });

        Schema::create('analysis_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained('case_analyses')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system', 'edit_marker']);
            $table->longText('content');

            $table->longText('analysis_snapshot')->nullable();
            $table->longText('analysis_diff_from_previous')->nullable();

            $table->string('target_section_id', 100)->nullable();
            $table->integer('selection_start')->nullable();
            $table->integer('selection_end')->nullable();

            $table->string('intent', 50)->nullable();
            $table->string('model_used', 100)->nullable();
            $table->integer('tokens_input')->nullable();
            $table->integer('tokens_output')->nullable();
            $table->integer('cost_inr_paise')->default(0);

            $table->timestamp('created_at')->useCurrent();

            $table->index(['analysis_id', 'created_at']);
        });

        Schema::create('analysis_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained('case_analyses')->cascadeOnDelete();
            $table->longText('content_md');
            $table->json('context_snapshot');
            $table->enum('created_by', ['user_edit', 'ai_edit', 'auto_save']);
            $table->foreignId('message_id')->nullable()->constrained('analysis_messages')->nullOnDelete();
            $table->string('label', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['analysis_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_snapshots');
        Schema::dropIfExists('analysis_messages');
        Schema::dropIfExists('case_analyses');
    }
};
