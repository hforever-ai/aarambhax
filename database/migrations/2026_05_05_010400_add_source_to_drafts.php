<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provenance pointers when a Draft was produced via "Convert to draft" from
 * either an Analysis or a Conversation. Lets the editor surface the source
 * context and lets us audit which AI flow seeded each draft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drafts', function (Blueprint $table) {
            $table->foreignId('source_analysis_id')->nullable()->after('case_id')
                ->constrained('case_analyses')->nullOnDelete();
            $table->foreignId('source_conversation_id')->nullable()->after('source_analysis_id')
                ->constrained('conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drafts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_conversation_id');
            $table->dropConstrainedForeignId('source_analysis_id');
        });
    }
};
