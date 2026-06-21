<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pyq_questions')) return;

        Schema::create('pyq_questions', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 30)->index();
            $table->string('topic', 100);
            $table->smallInteger('year');
            $table->enum('type', ['mcq', 'numerical'])->default('mcq');
            $table->text('question');
            $table->json('options')->nullable();
            $table->text('answer');
            $table->text('solution')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pyq_questions');
    }
};
