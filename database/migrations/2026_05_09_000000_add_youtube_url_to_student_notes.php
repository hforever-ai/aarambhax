<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_notes', function (Blueprint $table) {
            $table->string('youtube_url')->nullable()->after('image_path');
            $table->string('source_type')->default('upload')->after('youtube_url'); // upload | youtube | text
        });
    }

    public function down(): void
    {
        Schema::table('student_notes', function (Blueprint $table) {
            $table->dropColumn(['youtube_url', 'source_type']);
        });
    }
};
