<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Approval gate — new users default to 'pending' and cannot perform any
     * write actions until an admin approves them. Existing users at migration
     * time are auto-approved (grandfathered) so we don't lock anyone out.
     *
     * Admins (is_admin=true) can access the Filament /admin panel + approve
     * other users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('email')->index();
            $table->boolean('is_admin')->default(false)->after('status');
            $table->timestamp('approved_at')->nullable()->after('is_admin');
            $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('approved_at');
        });

        // Grandfather all existing users as approved
        DB::table('users')->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Admins: Ajay (hforever@gmail.com) + Vikash bhai (advvikashagrawal@gmail.com)
        // Match by email rather than user_id so this is correct on any DB
        // (dev/staging/prod) where IDs may differ.
        DB::table('users')
            ->whereIn('email', [
                'hforever@gmail.com',
                'advvikashagrawal@gmail.com',
            ])
            ->update(['is_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'is_admin', 'approved_at', 'approved_by_user_id']);
        });
    }
};
