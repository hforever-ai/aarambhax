<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pattern C async pipeline state on CaseAnalysis.
 *   pipeline_status: queued → running → done | failed
 *   pipeline_stage:  human-readable current step ("ingesting (2/3)", "analysing", "complete")
 *   pipeline_progress: 0..100 integer for the progress bar
 *   pipeline_started_at: when the job picked it up (for elapsed display)
 *   pipeline_finished_at: when done/failed
 *   pipeline_error: failure detail
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_analyses', function (Blueprint $table) {
            $table->string('pipeline_status', 20)->default('done')->after('status');
            $table->string('pipeline_stage', 100)->nullable()->after('pipeline_status');
            $table->unsignedTinyInteger('pipeline_progress')->default(100)->after('pipeline_stage');
            $table->timestamp('pipeline_started_at')->nullable()->after('pipeline_progress');
            $table->timestamp('pipeline_finished_at')->nullable()->after('pipeline_started_at');
            $table->text('pipeline_error')->nullable()->after('pipeline_finished_at');
        });
    }

    public function down(): void
    {
        Schema::table('case_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_status', 'pipeline_stage', 'pipeline_progress',
                'pipeline_started_at', 'pipeline_finished_at', 'pipeline_error',
            ]);
        });
    }
};
