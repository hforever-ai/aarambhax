<?php

namespace App\Services\Quota;

use App\Exceptions\QuotaExceededException;
use App\Models\ApiUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Per-user API usage quota — protects against runaway loops, abuse, and
 * cost overruns. Limits are configurable via .env:
 *
 *   USER_QUOTA_PER_HOUR  (default 60)
 *   USER_QUOTA_PER_DAY   (default 200)
 *
 * Quotas are NOT applied to admins (Vikash bhai or Ajay) — they can
 * exceed limits for ops/diagnostics work. They're also off in local dev
 * unless explicitly enabled.
 *
 * Action types used: ingest, architect, analyse, karya, chat, draft.
 *
 * Usage:
 *   $quota = app(UserApiQuota::class);
 *   $quota->checkAndConsume($user, 'karya', 1, $karya);
 *   // throws QuotaExceededException if limit would be exceeded
 */
class UserApiQuota
{
    public function __construct(
        public readonly int $perHour = 60,
        public readonly int $perDay = 200,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            perHour: (int) config('services.aarambh_app.quota_per_hour', 60),
            perDay: (int) config('services.aarambh_app.quota_per_day', 200),
        );
    }

    /**
     * Check if the user has remaining quota for $geminiCalls more calls.
     * If yes: write a usage log row and return.
     * If no: throw QuotaExceededException.
     *
     * Admins bypass quota.
     */
    public function checkAndConsume(
        User $user,
        string $actionType,
        int $geminiCalls = 1,
        ?object $reference = null,
        array $callMeta = [],
    ): void {
        if ($user->isAdmin()) {
            // Still log it for visibility, but don't gate
            $this->log($user, $actionType, $geminiCalls, $reference, $callMeta);
            return;
        }

        $usage = $this->currentUsage($user);

        if ($usage['hour'] + $geminiCalls > $this->perHour) {
            throw new QuotaExceededException(
                window: 'hourly',
                limit: $this->perHour,
                used: $usage['hour'],
                resetSeconds: $usage['hour_reset_seconds'],
            );
        }

        if ($usage['day'] + $geminiCalls > $this->perDay) {
            throw new QuotaExceededException(
                window: 'daily',
                limit: $this->perDay,
                used: $usage['day'],
                resetSeconds: $usage['day_reset_seconds'],
            );
        }

        $this->log($user, $actionType, $geminiCalls, $reference, $callMeta);
    }

    /**
     * Update the most recent log row for $user / $reference with post-call
     * metadata (tier, model, tokens, cost). Use after the Gemini call returns
     * so we capture actual values rather than estimates at gate time.
     */
    public function updateLastLog(User $user, ?object $reference, array $callMeta): void
    {
        $q = ApiUsageLog::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(1);
        if ($reference) {
            $q->where('reference_type', $reference::class);
            if (property_exists($reference, 'id')) {
                $q->where('reference_id', $reference->id);
            }
        }
        $row = $q->first();
        if ($row) {
            $row->update([
                'tier' => $callMeta['tier'] ?? null,
                'model_used' => $callMeta['model_used'] ?? null,
                'cost_inr_paise' => (int) ($callMeta['cost_inr_paise'] ?? 0),
                'paid_equivalent_paise' => (int) ($callMeta['paid_equivalent_paise'] ?? 0),
                'tokens_in' => (int) ($callMeta['tokens_in'] ?? 0),
                'tokens_out' => (int) ($callMeta['tokens_out'] ?? 0),
            ]);
        }
    }

    /**
     * Get current usage stats for a user — used by the dashboard usage meter
     * (no consumption, no exception). Returns counts in hour and day windows.
     *
     * @return array{hour:int,day:int,hour_reset_seconds:int,day_reset_seconds:int,per_hour:int,per_day:int,is_admin:bool}
     */
    public function currentUsage(User $user): array
    {
        $now = now();
        $hourAgo = $now->copy()->subHour();
        $dayAgo = $now->copy()->subDay();

        // One query, two grouped sums via CASE WHEN.
        $row = DB::table('api_usage_logs')
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN gemini_calls ELSE 0 END) AS hour_total', [$hourAgo])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN gemini_calls ELSE 0 END) AS day_total', [$dayAgo])
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $dayAgo)
            ->first();

        $hour = (int) ($row->hour_total ?? 0);
        $day = (int) ($row->day_total ?? 0);

        // Find when the oldest still-counted call was, so we can tell user
        // when their bucket resets. For hour bucket: oldest call within last hour.
        $oldestInHour = DB::table('api_usage_logs')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $hourAgo)
            ->min('created_at');
        $oldestInDay = DB::table('api_usage_logs')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $dayAgo)
            ->min('created_at');

        $hourResetSeconds = $oldestInHour
            ? max(0, 3600 - $now->diffInSeconds(\Carbon\Carbon::parse($oldestInHour)))
            : 0;
        $dayResetSeconds = $oldestInDay
            ? max(0, 86400 - $now->diffInSeconds(\Carbon\Carbon::parse($oldestInDay)))
            : 0;

        return [
            'hour' => $hour,
            'day' => $day,
            'hour_reset_seconds' => $hourResetSeconds,
            'day_reset_seconds' => $dayResetSeconds,
            'per_hour' => $this->perHour,
            'per_day' => $this->perDay,
            'is_admin' => $user->isAdmin(),
        ];
    }

    private function log(User $user, string $actionType, int $geminiCalls, ?object $reference, array $callMeta = []): void
    {
        ApiUsageLog::create([
            'user_id' => $user->id,
            'action_type' => $actionType,
            'tier' => $callMeta['tier'] ?? null,
            'model_used' => $callMeta['model_used'] ?? null,
            'gemini_calls' => $geminiCalls,
            'cost_inr_paise' => (int) ($callMeta['cost_inr_paise'] ?? 0),
            'paid_equivalent_paise' => (int) ($callMeta['paid_equivalent_paise'] ?? 0),
            'tokens_in' => (int) ($callMeta['tokens_in'] ?? 0),
            'tokens_out' => (int) ($callMeta['tokens_out'] ?? 0),
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference && property_exists($reference, 'id') ? $reference->id : null,
        ]);
    }
}
