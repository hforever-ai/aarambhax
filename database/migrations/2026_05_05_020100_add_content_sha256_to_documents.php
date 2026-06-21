<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent document ingestion. Hash uploaded file contents; on re-upload
 * (e.g. after a 504 retry), match by (case_id, content_sha256) and skip
 * if already present. Solves "duplicate Documents on retry" bug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->char('content_sha256', 64)->nullable()->after('bytes');
            $table->index(['case_id', 'content_sha256'], 'docs_case_sha_idx');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('docs_case_sha_idx');
            $table->dropColumn('content_sha256');
        });
    }
};
