<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'kind' column so each case can have one conversation per work mode.
     * Values: brainstorm | bahas | cross_exam | samjhao | analysis | draft.
     * Existing rows are all from the original Brainstorm flow → default 'brainstorm'.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('kind', 20)->default('brainstorm')->after('case_id')->index();
        });

        // Grandfather: every existing Conversation row is from the legacy
        // single-thread era; mark all as 'brainstorm' so they remain visible
        // on the Brainstorm tab.
        DB::table('conversations')->update(['kind' => 'brainstorm']);
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });
    }
};
