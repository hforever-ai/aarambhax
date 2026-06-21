<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('case_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('forum', 50);                  // cg_hc | cg_district | cg_revenue | tribunal
            $table->string('court_name', 200)->nullable();
            $table->string('case_no', 100)->nullable();
            $table->string('jurisdiction', 100)->nullable();
            $table->string('category', 50)->nullable();   // civil | criminal | family | revenue | special_act
            $table->enum('status', ['active', 'closed', 'on_hold'])->default('active');
            $table->string('opposing_party', 500)->nullable();

            // Revenue-specific fields
            $table->string('khasra_no', 100)->nullable();
            $table->string('khata_no', 100)->nullable();
            $table->string('khatauni_no', 100)->nullable();
            $table->string('gram', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            $table->string('jila', 100)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('forum');
        });

        Schema::create('hearings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('case_records')->cascadeOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('court', 200)->nullable();
            $table->string('purpose', 200)->nullable();
            $table->text('outcome')->nullable();
            $table->date('next_date')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index('case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hearings');
        Schema::dropIfExists('case_records');
        Schema::dropIfExists('clients');
    }
};
