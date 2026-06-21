<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name_en', 200);
            $table->string('name_hi', 200)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_hi')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_categories');
    }
};
