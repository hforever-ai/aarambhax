<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user API usage log — one row per Gemini-cost-incurring action.
     *
     * Used by UserApiQuota service to check 60/hour + 200/day limits.
     * Indexed on (user_id, created_at) so quota lookups are sub-millisecond.
     *
     * action_type values: ingest | architect | analyse | karya | chat | draft
     *
     * gemini_calls is the count of underlying Gemini calls for this action
     * (e.g. ingest=1, parallel-ingest-4-docs as 1 row with gemini_calls=4,
     * or 4 rows with gemini_calls=1 each — caller decides).
     */
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action_type', 32)->index();
            $table->unsignedSmallInteger('gemini_calls')->default(1);
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            // Composite index on (user_id, created_at) — the hot lookup pattern
            // for quota checks (sum gemini_calls where user_id=? and created_at>=?).
            $table->index(['user_id', 'created_at'], 'api_usage_user_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
