<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('bar_enrolment_no')->nullable();
            $table->string('designation')->nullable();
            $table->text('bio_en')->nullable();
            $table->text('bio_hi')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('social_linkedin', 255)->nullable();
            $table->string('social_x', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
