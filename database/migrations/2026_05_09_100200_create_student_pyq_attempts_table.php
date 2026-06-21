<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_pyq_attempts')) return;

        Schema::create('student_pyq_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('pyq_id')->constrained('pyq_questions')->cascadeOnDelete();
            $table->foreignId('note_id')->nullable()->constrained('student_notes')->nullOnDelete();
            $table->enum('status', ['understood', 'not_understood']);
            $table->timestamps();

            $table->unique(['user_id', 'pyq_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_pyq_attempts');
    }
};
