<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->enum('language_pref', ['en', 'hi', 'both'])->default('en');
            $table->string('source', 100)->nullable();
            $table->boolean('confirmed')->default(false);
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('newsletter_subscribers');
    }
};
