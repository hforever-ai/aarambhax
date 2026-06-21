<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // advocate = full access (default for all existing users)
            // student  = simplified dashboard, AI tools access, read-only on assigned cases
            $table->enum('role', ['advocate', 'student'])->default('advocate')->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
