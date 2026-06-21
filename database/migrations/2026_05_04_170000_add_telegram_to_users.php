<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id', 50)->nullable()->after('email');
            $table->string('telegram_pairing_code', 20)->nullable()->after('telegram_chat_id');
            $table->boolean('telegram_alerts_enabled')->default(true)->after('telegram_pairing_code');
            $table->string('bar_enrolment_no', 50)->nullable()->after('telegram_alerts_enabled');
            $table->text('signature_block_en')->nullable();
            $table->text('signature_block_hi')->nullable();
            $table->text('chamber_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id', 'telegram_pairing_code', 'telegram_alerts_enabled',
                'bar_enrolment_no', 'signature_block_en', 'signature_block_hi', 'chamber_address',
            ]);
        });
    }
};
