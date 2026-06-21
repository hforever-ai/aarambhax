<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_pipeline_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->enum('state', [
                'idea',
                'outline_draft', 'outline_review', 'outline_approved',
                'draft_en', 'en_review', 'en_approved',
                'draft_hi', 'hi_review', 'both_approved',
                'assets_generating', 'assets_ready',
                'published', 'archived',
            ])->default('idea');
            $table->foreignId('current_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('outline_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('state');
            $table->index(['state', 'updated_at']);
        });

        Schema::create('post_pipeline_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_run_id')->constrained('post_pipeline_runs')->cascadeOnDelete();
            $table->enum('step_type', [
                'outline_gen', 'draft_gen_en', 'translate_hi',
                'image_gen', 'faq_gen', 'citation_extract', 'citation_verify',
            ]);
            $table->string('model_used', 100)->nullable();
            $table->longText('prompt')->nullable();
            $table->longText('raw_output')->nullable();
            $table->longText('parsed_output')->nullable();
            $table->longText('human_edits')->nullable();
            $table->integer('tokens_input')->nullable();
            $table->integer('tokens_output')->nullable();
            $table->integer('cost_inr_paise')->default(0);
            $table->integer('duration_ms')->nullable();
            $table->enum('status', ['success', 'failed', 'retried', 'approved', 'rejected'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('step_type');
            $table->index(['pipeline_run_id', 'created_at']);
        });

        Schema::create('post_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->enum('citation_type', ['statute_section', 'judgment', 'rule', 'notification']);
            $table->string('raw_text', 500);
            $table->string('statute_code', 20)->nullable();
            $table->string('section_no', 20)->nullable();
            $table->string('judgment_id', 100)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'suspect', 'hallucinated'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->enum('verified_by', ['auto', 'human'])->nullable();
            $table->text('verifier_notes')->nullable();
            $table->integer('position_in_post')->nullable();
            $table->timestamps();

            $table->index('verification_status');
            $table->index(['statute_code', 'section_no']);
        });

        // Add a single, denormalised pipeline pointer on posts for fast queries
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('current_pipeline_run_id')->nullable()->after('view_count');
            $table->index('current_pipeline_run_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['current_pipeline_run_id']);
            $table->dropColumn('current_pipeline_run_id');
        });

        Schema::dropIfExists('post_citations');
        Schema::dropIfExists('post_pipeline_steps');
        Schema::dropIfExists('post_pipeline_runs');
    }
};
