<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->enum('language', ['en', 'hi'])->default('en');
            $table->unsignedBigInteger('translation_group_id')->nullable();
            $table->foreignId('category_id')->constrained('post_categories');
            $table->enum('archetype', [
                'section_mapping', 'drafting_walkthrough', 'format_sample',
                'comparison', 'checklist', 'case_study', 'general',
            ])->default('general');

            $table->string('title');
            $table->string('subtitle', 500)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->longText('body_html')->nullable();

            $table->string('hero_image_url', 500)->nullable();
            $table->string('hero_image_alt')->nullable();

            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_image_url', 500)->nullable();

            $table->foreignId('author_id')->constrained('authors');
            $table->enum('status', ['draft', 'review', 'scheduled', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            $table->integer('reading_time_minutes')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);

            $table->boolean('has_downloadable_sample')->default(false);
            $table->string('sample_draft_url', 500)->nullable();

            $table->string('related_app_route')->nullable();

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category_id');
            $table->index('translation_group_id');
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('posts');
    }
};
