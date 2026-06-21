<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject')->nullable();   // Civil Law, Criminal Law, etc.
            $table->string('class_name')->nullable(); // LLB Year 1, Semester 3, etc.
            $table->string('title')->nullable();
            $table->string('image_path')->nullable();
            $table->text('note_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notes');
    }
};
