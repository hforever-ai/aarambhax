<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('student_notes', 'matched_topics')) return;

        Schema::table('student_notes', function (Blueprint $table) {
            $table->json('matched_topics')->nullable()->after('ai_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_notes', function (Blueprint $table) {
            $table->dropColumn('matched_topics');
        });
    }
};
