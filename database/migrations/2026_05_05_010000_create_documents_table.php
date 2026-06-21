<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Case-scoped uploaded client files (FIRs, lower court orders, photos, etc.)
 * promoted to first-class entities. Previously these only existed inside a
 * Draft's context_user_prefs JSON blob; that prevented multi-doc analysis
 * and reuse across analyses + chats + drafts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('case_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('original_filename', 500);
            $table->string('stored_path', 500);            // storage/app/cases/{case_id}/...
            $table->string('mime_type', 100);
            $table->unsignedInteger('bytes')->default(0);

            // Populated by /v1/ingest
            $table->string('detected_doc_type', 80)->nullable();   // FIR | charge_sheet | lower_court_order | ...
            $table->string('language', 20)->nullable();            // en | hi | bilingual
            $table->longText('ocr_text')->nullable();              // cleaned markdown after Gemini Flash multimodal
            $table->json('structured_fields')->nullable();         // {fir_no, ps, sections, complainant, accused, ...}

            $table->string('ingest_model_used', 100)->nullable();
            $table->unsignedInteger('ingest_tokens_in')->nullable();
            $table->unsignedInteger('ingest_tokens_out')->nullable();
            $table->float('ingest_confidence')->nullable();
            $table->timestamp('ingested_at')->nullable();

            $table->timestamps();

            $table->index(['case_id', 'detected_doc_type']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
