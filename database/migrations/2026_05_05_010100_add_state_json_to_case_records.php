<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shared per-Case working memory:
 *   extracted_facts: structured case profile (parties, chronology, sections, ...)
 *   missing_documents: facts the AI flagged as needed but absent
 *   legal_theories: ranked filings the advocate could pursue
 *   draft_preferences: forum, language, draft_type once chosen
 *
 * Read + written by analyse, chat, and draft flows. Single source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->json('state_json')->nullable()->after('opposing_party');
        });
    }

    public function down(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->dropColumn('state_json');
        });
    }
};
