<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draft_catalogue', function (Blueprint $table) {
            $table->id();
            $table->string('source_path', 500)->unique();
            $table->string('source_filename', 300);
            $table->unsignedInteger('source_bytes')->nullable();

            $table->string('forum', 50)->nullable()->index();
            $table->string('court', 200)->nullable();
            $table->string('draft_type', 200)->nullable()->index();
            $table->string('language', 10)->nullable()->index();
            $table->string('case_category', 50)->nullable()->index();
            $table->string('state', 50)->nullable();
            $table->string('district', 100)->nullable();

            $table->json('required_inputs')->nullable();
            $table->json('optional_inputs')->nullable();
            $table->json('forbidden_inputs')->nullable();

            $table->json('statutes')->nullable();
            $table->json('template_outline')->nullable();

            $table->float('ai_confidence')->nullable();
            $table->boolean('reviewed_by_advocate')->default(false);
            $table->text('cleaned_text')->nullable();

            $table->json('raw_ai_response')->nullable();
            $table->string('ai_model_used', 100)->nullable();
            $table->unsignedInteger('tokens_input')->nullable();
            $table->unsignedInteger('tokens_output')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draft_catalogue');
    }
};
