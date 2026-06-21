<?php

namespace App\Filament\Pages;

use App\Models\ApiUsageLog;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only cost dashboard. Aggregates api_usage_logs by user + day so
 * we can see per-advocate AI cost over the last 30 days.
 *
 * - Today's totals at the top (calls, paid cost, free-tier savings)
 * - Per-day series for the last 30 days
 * - Per-user breakdown (today + month-to-date)
 *
 * Only admins (is_admin=true) can access — gated by canAccessPanel() on the
 * User model and an additional check here.
 */
class CostDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-rupee';

    protected static ?string $navigationGroup = 'Aarambh Legal';

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Cost dashboard';

    protected static ?string $navigationLabel = 'Costs';

    protected static string $view = 'filament.pages.cost-dashboard';

    public static function canAccess(): bool
    {
        return auth()->check() && (bool) auth()->user()?->is_admin;
    }

    /**
     * Aggregate today's usage across all users.
     *
     * @return array{calls:int, cost_paise:int, savings_paise:int, free_calls:int, paid_calls:int}
     */
    public function getTodaySummary(): array
    {
        $today = today();
        $row = DB::table('api_usage_logs')
            ->selectRaw('
                COUNT(*) AS calls,
                COALESCE(SUM(cost_inr_paise), 0) AS cost_paise,
                COALESCE(SUM(paid_equivalent_paise), 0) AS paid_eq_paise,
                COALESCE(SUM(CASE WHEN tier = "free" THEN gemini_calls ELSE 0 END), 0) AS free_calls,
                COALESCE(SUM(CASE WHEN tier = "paid" THEN gemini_calls ELSE 0 END), 0) AS paid_calls
            ')
            ->where('created_at', '>=', $today)
            ->first();

        $costPaise = (int) ($row->cost_paise ?? 0);
        $paidEqPaise = (int) ($row->paid_eq_paise ?? 0);

        return [
            'calls' => (int) ($row->calls ?? 0),
            'cost_paise' => $costPaise,
            'savings_paise' => max(0, $paidEqPaise - $costPaise),
            'free_calls' => (int) ($row->free_calls ?? 0),
            'paid_calls' => (int) ($row->paid_calls ?? 0),
        ];
    }

    /**
     * Month-to-date totals.
     */
    public function getMonthSummary(): array
    {
        $start = now()->startOfMonth();
        $row = DB::table('api_usage_logs')
            ->selectRaw('
                COUNT(*) AS calls,
                COALESCE(SUM(cost_inr_paise), 0) AS cost_paise,
                COALESCE(SUM(paid_equivalent_paise), 0) AS paid_eq_paise
            ')
            ->where('created_at', '>=', $start)
            ->first();

        return [
            'calls' => (int) ($row->calls ?? 0),
            'cost_paise' => (int) ($row->cost_paise ?? 0),
            'savings_paise' => max(0, ((int) $row->paid_eq_paise) - ((int) $row->cost_paise)),
        ];
    }

    /**
     * Per-day series for the last 30 days (oldest first).
     *
     * @return array<int, array{date:string, calls:int, cost_paise:int, paid_eq_paise:int}>
     */
    public function getDailySeries(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $rows = DB::table('api_usage_logs')
            ->selectRaw('
                DATE(created_at) AS day,
                COUNT(*) AS calls,
                COALESCE(SUM(cost_inr_paise), 0) AS cost_paise,
                COALESCE(SUM(paid_equivalent_paise), 0) AS paid_eq_paise
            ')
            ->where('created_at', '>=', $start)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // Fill in zero days
        $out = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $row = $rows->get($date);
            $out[] = [
                'date' => $date,
                'calls' => $row ? (int) $row->calls : 0,
                'cost_paise' => $row ? (int) $row->cost_paise : 0,
                'paid_eq_paise' => $row ? (int) $row->paid_eq_paise : 0,
            ];
        }
        return $out;
    }

    /**
     * Per-user breakdown for current month with today's totals as a sub-aggregation.
     *
     * @return Collection<int, object>
     */
    public function getPerUserBreakdown(): Collection
    {
        $startMonth = now()->startOfMonth();
        $today = today();

        return DB::table('api_usage_logs as l')
            ->join('users as u', 'u.id', '=', 'l.user_id')
            ->selectRaw('
                u.id,
                u.name,
                u.email,
                u.is_admin,
                COUNT(*) AS month_calls,
                COALESCE(SUM(l.cost_inr_paise), 0) AS month_cost_paise,
                COALESCE(SUM(l.paid_equivalent_paise), 0) AS month_paid_eq_paise,
                COALESCE(SUM(CASE WHEN l.created_at >= ? THEN 1 ELSE 0 END), 0) AS today_calls,
                COALESCE(SUM(CASE WHEN l.created_at >= ? THEN l.cost_inr_paise ELSE 0 END), 0) AS today_cost_paise
            ', [$today, $today])
            ->where('l.created_at', '>=', $startMonth)
            ->groupBy('u.id', 'u.name', 'u.email', 'u.is_admin')
            ->orderByRaw('month_cost_paise DESC, month_calls DESC')
            ->get();
    }

    public function rupees(int $paise): string
    {
        return '₹'.number_format($paise / 100, 2);
    }
}
