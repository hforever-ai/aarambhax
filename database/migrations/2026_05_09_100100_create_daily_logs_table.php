<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_logs')) return;

        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $table->date('log_date');
            $table->string('studied_topics', 500)->nullable();
            $table->decimal('hours_studied', 4, 1)->nullable();
            $table->unsignedTinyInteger('mood')->nullable();
            $table->string('food', 300)->nullable();
            $table->decimal('expenses', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};
