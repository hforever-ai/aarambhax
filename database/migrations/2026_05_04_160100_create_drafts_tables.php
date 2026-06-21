<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('case_records')->nullOnDelete();

            $table->string('title', 500);
            $table->string('forum', 50);
            $table->string('category', 50);
            $table->string('draft_type', 100);
            $table->enum('language', ['en', 'hi', 'bilingual'])->default('en');

            // The "living context" — preserved across every edit
            $table->json('context_facts');                   // parties, case_no, jurisdiction, key_facts, dates
            $table->json('context_legal')->nullable();       // cited_sections, cited_judgments, statutes_in_play
            $table->json('context_user_prefs')->nullable();  // signature_block, banned_phrases

            $table->longText('current_content_md');
            $table->longText('current_content_html')->nullable();

            $table->enum('status', ['drafting', 'editing', 'finalized', 'exported'])->default('drafting');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('case_id');
        });

        Schema::create('draft_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system', 'edit_marker']);
            $table->longText('content');

            // For edit_markers: snapshot at this point
            $table->longText('draft_snapshot')->nullable();
            $table->longText('draft_diff_from_previous')->nullable();

            // Surgical edit targeting
            $table->string('target_section_id', 100)->nullable();
            $table->integer('selection_start')->nullable();
            $table->integer('selection_end')->nullable();

            $table->string('intent', 50)->nullable();      // initial_draft | rewrite_section | tighten | add_citation | free_form ...
            $table->string('model_used', 100)->nullable();
            $table->integer('tokens_input')->nullable();
            $table->integer('tokens_output')->nullable();
            $table->integer('cost_inr_paise')->default(0);

            $table->timestamp('created_at')->useCurrent();

            $table->index(['draft_id', 'created_at']);
        });

        Schema::create('draft_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained()->cascadeOnDelete();
            $table->longText('content_md');
            $table->json('context_snapshot');
            $table->enum('created_by', ['user_edit', 'ai_edit', 'auto_save']);
            $table->foreignId('message_id')->nullable()->constrained('draft_messages')->nullOnDelete();
            $table->string('label', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['draft_id', 'created_at']);
        });

        Schema::create('draft_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained()->cascadeOnDelete();
            $table->enum('citation_type', ['statute_section', 'judgment', 'rule']);
            $table->string('raw_text', 500);
            $table->string('statute_code', 20)->nullable();
            $table->string('section_no', 20)->nullable();
            $table->string('judgment_id', 100)->nullable();
            $table->integer('position_in_draft')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'suspect', 'hallucinated'])->default('pending');
            $table->foreignId('added_in_message_id')->nullable()->constrained('draft_messages')->nullOnDelete();
            $table->foreignId('removed_in_message_id')->nullable()->constrained('draft_messages')->nullOnDelete();
            $table->timestamps();

            $table->index('draft_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draft_citations');
        Schema::dropIfExists('draft_snapshots');
        Schema::dropIfExists('draft_messages');
        Schema::dropIfExists('drafts');
    }
};
