<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalized search index. One row per searchable thing:
 *   case      — case title + meta
 *   document  — document filename + ocr_text
 *   message   — chat message content
 *   draft     — draft markdown body
 *
 * MySQL FULLTEXT index on (title, body). Upstream changes are mirrored
 * here via Eloquent observers, plus a one-shot rebuild artisan command.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('case_records')->cascadeOnDelete();
            $table->string('entity_type', 20);
            $table->unsignedBigInteger('entity_id');
            $table->string('title', 500)->nullable();
            $table->mediumText('body');
            $table->string('snippet_url', 500)->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id']);
            $table->index(['user_id', 'case_id']);
            $table->index('updated_at');
        });

        // FULLTEXT only on MySQL/MariaDB. SQLite (used in dev) skips it —
        // we'll fall back to LIKE queries when the driver isn't MySQL.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE search_index ADD FULLTEXT search_ft (title, body)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_index');
    }
};
