<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 255);
            $table->longText('body_md');
            $table->longText('body_html')->nullable();
            $table->enum('language_filter', ['en', 'hi', 'both'])->default('both');
            $table->enum('status', ['draft', 'queued', 'sending', 'sent', 'failed'])->default('draft');
            $table->integer('recipient_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_broadcasts');
    }
};
