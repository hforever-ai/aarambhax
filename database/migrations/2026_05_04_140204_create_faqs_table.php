<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 150)->unique();
            $table->enum('language', ['en', 'hi'])->default('en');
            $table->unsignedBigInteger('translation_group_id')->nullable();

            $table->string('question', 500);
            $table->longText('answer');
            $table->longText('answer_html')->nullable();

            $table->string('topic', 100)->nullable();
            $table->string('related_statute_code', 20)->nullable();
            $table->string('related_section_no', 20)->nullable();

            $table->integer('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);

            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);

            $table->timestamps();

            $table->index('topic');
            $table->index(['related_statute_code', 'related_section_no']);
            $table->index(['is_featured', 'display_order']);
            $table->index(['is_published', 'language']);
        });

        Schema::create('post_faq', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faq_id')->constrained()->cascadeOnDelete();
            $table->integer('display_order')->default(0);
            $table->primary(['post_id', 'faq_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_faq');
        Schema::dropIfExists('faqs');
    }
};
