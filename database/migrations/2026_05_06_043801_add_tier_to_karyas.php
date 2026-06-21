<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tier metadata on Karyas — which Gemini tier was used (paid/free), what
     * the call would have cost on paid tier (paid_equivalent_paise — useful
     * for "you saved X by using free tier" displays), and how many PII items
     * were redacted before sending (audit trail for free-tier calls).
     *
     * Same columns will be added to api_usage_logs separately so per-user
     * cost dashboards can aggregate.
     */
    public function up(): void
    {
        Schema::table('karyas', function (Blueprint $table) {
            $table->string('tier', 8)->nullable()->after('model_used');
            $table->unsignedInteger('paid_equivalent_paise')->default(0)->after('cost_inr_paise');
            $table->unsignedSmallInteger('pii_redactions')->default(0)->after('paid_equivalent_paise');
        });

        Schema::table('api_usage_logs', function (Blueprint $table) {
            $table->string('tier', 8)->nullable()->after('action_type');
            $table->string('model_used', 100)->nullable()->after('tier');
            $table->unsignedInteger('cost_inr_paise')->default(0)->after('gemini_calls');
            $table->unsignedInteger('paid_equivalent_paise')->default(0)->after('cost_inr_paise');
            $table->unsignedInteger('tokens_in')->default(0)->after('paid_equivalent_paise');
            $table->unsignedInteger('tokens_out')->default(0)->after('tokens_in');
        });
    }

    public function down(): void
    {
        Schema::table('karyas', function (Blueprint $table) {
            $table->dropColumn(['tier', 'paid_equivalent_paise', 'pii_redactions']);
        });
        Schema::table('api_usage_logs', function (Blueprint $table) {
            $table->dropColumn(['tier', 'model_used', 'cost_inr_paise', 'paid_equivalent_paise', 'tokens_in', 'tokens_out']);
        });
    }
};
