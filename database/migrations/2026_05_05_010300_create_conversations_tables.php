<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-form chat per Case. Different shape from CaseAnalysis: no primary
 * markdown artefact — just a thread of messages. Long conversations
 * compressed via context_summary_md (rolling-window summarisation at
 * 30-message threshold).
 *
 * Optional source_analysis_id when chat starts from an Analysis.
 * Optional converted_draft_id once user clicks "Convert to draft".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('case_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title', 500)->nullable();
            $table->enum('language', ['en', 'hi', 'bilingual'])->default('en');
            $table->enum('status', ['active', 'archived', 'converted_to_draft'])->default('active');

            // Rolling-summary memory snapshot. Replaces oldest 20 messages once
            // conversation crosses 30 turns / 40k tokens. See ConversationOrchestrator.
            $table->longText('context_summary_md')->nullable();
            $table->unsignedInteger('total_tokens_in')->default(0);
            $table->unsignedInteger('total_tokens_out')->default(0);

            // Provenance — chat may start from an analysis, or be free-standing
            $table->foreignId('source_analysis_id')->nullable()->constrained('case_analyses')->nullOnDelete();
            $table->foreignId('converted_draft_id')->nullable()->constrained('drafts')->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('case_id');
        });

        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->longText('content');

            $table->string('model_used', 100)->nullable();
            $table->integer('tokens_input')->nullable();
            $table->integer('tokens_output')->nullable();
            $table->integer('cost_inr_paise')->default(0);

            // Soft-deletion when oldest messages get folded into context_summary_md
            $table->boolean('summarised_into_context')->default(false);

            $table->timestamp('created_at')->useCurrent();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['conversation_id', 'summarised_into_context'], 'conv_msg_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversations');
    }
};
