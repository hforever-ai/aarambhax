<x-filament-panels::page>
    @php
        $today = $this->getTodaySummary();
        $month = $this->getMonthSummary();
        $series = $this->getDailySeries();
        $perUser = $this->getPerUserBreakdown();
        $maxCalls = max(1, max(array_column($series, 'calls')));
    @endphp

    {{-- Top stat cards --}}
    <div class="cd-stats">
        <div class="cd-stat-card">
            <p class="cd-stat-label">Today</p>
            <p class="cd-stat-value">{{ $this->rupees($today['cost_paise']) }}</p>
            <p class="cd-stat-meta">
                {{ $today['calls'] }} calls · {{ $today['free_calls'] }} free, {{ $today['paid_calls'] }} paid
            </p>
            @if($today['savings_paise'] > 0)
                <p class="cd-stat-savings">+{{ $this->rupees($today['savings_paise']) }} saved via free tier</p>
            @endif
        </div>
        <div class="cd-stat-card">
            <p class="cd-stat-label">This month</p>
            <p class="cd-stat-value">{{ $this->rupees($month['cost_paise']) }}</p>
            <p class="cd-stat-meta">{{ $month['calls'] }} calls · since {{ now()->startOfMonth()->format('d M') }}</p>
            @if($month['savings_paise'] > 0)
                <p class="cd-stat-savings">+{{ $this->rupees($month['savings_paise']) }} saved this month</p>
            @endif
        </div>
        <div class="cd-stat-card">
            <p class="cd-stat-label">Active users this month</p>
            <p class="cd-stat-value">{{ $perUser->count() }}</p>
            <p class="cd-stat-meta">{{ $perUser->where('today_calls', '>', 0)->count() }} active today</p>
        </div>
    </div>

    {{-- 30-day chart (CSS bars, no JS) --}}
    <div class="cd-card">
        <div class="cd-card-head">
            <h2 class="cd-card-title">Last 30 days</h2>
            <p class="cd-card-sub">Calls per day across all users.</p>
        </div>
        <div class="cd-chart">
            @foreach($series as $day)
                @php
                    $h = $day['calls'] > 0 ? max(8, round(($day['calls'] / $maxCalls) * 100)) : 2;
                    $isToday = $day['date'] === today()->format('Y-m-d');
                @endphp
                <div class="cd-chart-col" title="{{ $day['date'] }} · {{ $day['calls'] }} calls · {{ $this->rupees($day['cost_paise']) }}">
                    <div class="cd-chart-bar {{ $isToday ? 'is-today' : '' }}" style="height: {{ $h }}%;"></div>
                    <span class="cd-chart-label">{{ \Carbon\Carbon::parse($day['date'])->format('d') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Per-user breakdown --}}
    <div class="cd-card">
        <div class="cd-card-head">
            <h2 class="cd-card-title">Per-user breakdown</h2>
            <p class="cd-card-sub">Sorted by month-to-date cost.</p>
        </div>
        @if($perUser->isEmpty())
            <p class="cd-empty">No usage logged this month.</p>
        @else
            <div class="cd-table-wrap">
                <table class="cd-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th class="cd-num">Today calls</th>
                            <th class="cd-num">Today cost</th>
                            <th class="cd-num">Month calls</th>
                            <th class="cd-num">Month cost</th>
                            <th class="cd-num">Saved (free tier)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perUser as $u)
                            @php
                                $savedMonth = max(0, $u->month_paid_eq_paise - $u->month_cost_paise);
                            @endphp
                            <tr>
                                <td>
                                    <div class="cd-user-cell">
                                        <span class="cd-user-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                        <div>
                                            <div class="cd-user-name">{{ $u->name }}</div>
                                            <div class="cd-user-email">{{ $u->email }}</div>
                                        </div>
                                        @if($u->is_admin)
                                            <span class="cd-user-admin">Admin</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="cd-num">{{ $u->today_calls }}</td>
                                <td class="cd-num">{{ $this->rupees((int) $u->today_cost_paise) }}</td>
                                <td class="cd-num">{{ $u->month_calls }}</td>
                                <td class="cd-num"><strong>{{ $this->rupees((int) $u->month_cost_paise) }}</strong></td>
                                <td class="cd-num cd-saved">
                                    @if($savedMonth > 0)
                                        +{{ $this->rupees((int) $savedMonth) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <style>
        .cd-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .cd-stat-card {
            background: rgb(255 255 255 / 0.6);
            border: 1px solid rgb(0 0 0 / 0.08);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
        }
        .dark .cd-stat-card {
            background: rgb(255 255 255 / 0.04);
            border-color: rgb(255 255 255 / 0.08);
        }
        .cd-stat-label {
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: rgb(107 114 128);
            margin: 0 0 0.5rem;
        }
        .cd-stat-value {
            font-size: 1.875rem;
            font-weight: 600;
            color: rgb(17 24 39);
            margin: 0;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }
        .dark .cd-stat-value { color: rgb(243 244 246); }
        .cd-stat-meta {
            font-size: 0.8125rem;
            color: rgb(107 114 128);
            margin: 0.5rem 0 0;
        }
        .cd-stat-savings {
            font-size: 0.75rem;
            color: rgb(34 197 94);
            font-weight: 600;
            margin: 0.4rem 0 0;
        }

        .cd-card {
            background: rgb(255 255 255 / 0.6);
            border: 1px solid rgb(0 0 0 / 0.08);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .dark .cd-card {
            background: rgb(255 255 255 / 0.04);
            border-color: rgb(255 255 255 / 0.08);
        }
        .cd-card-head { margin-bottom: 1.25rem; }
        .cd-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: rgb(17 24 39);
            margin: 0 0 0.25rem;
        }
        .dark .cd-card-title { color: rgb(243 244 246); }
        .cd-card-sub {
            font-size: 0.8125rem;
            color: rgb(107 114 128);
            margin: 0;
        }

        /* Bar chart */
        .cd-chart {
            display: flex;
            align-items: flex-end;
            gap: 0.25rem;
            height: 140px;
            padding: 0 0.25rem;
        }
        .cd-chart-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            min-width: 0;
            gap: 0.25rem;
            cursor: help;
        }
        .cd-chart-bar {
            width: 100%;
            min-height: 2px;
            background: linear-gradient(180deg, rgb(245 158 11) 0%, rgb(217 119 6) 100%);
            border-radius: 3px 3px 0 0;
            transition: opacity 200ms ease-out;
            opacity: 0.85;
        }
        .cd-chart-col:hover .cd-chart-bar { opacity: 1; }
        .cd-chart-bar.is-today {
            background: linear-gradient(180deg, rgb(34 197 94) 0%, rgb(22 163 74) 100%);
        }
        .cd-chart-label {
            font-size: 0.625rem;
            color: rgb(107 114 128);
            font-variant-numeric: tabular-nums;
        }

        /* Table */
        .cd-table-wrap { overflow-x: auto; }
        .cd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .cd-table th, .cd-table td {
            padding: 0.75rem 0.875rem;
            text-align: left;
            border-bottom: 1px solid rgb(0 0 0 / 0.06);
        }
        .dark .cd-table th, .dark .cd-table td { border-bottom-color: rgb(255 255 255 / 0.06); }
        .cd-table th {
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
            color: rgb(107 114 128);
        }
        .cd-num { text-align: right; font-variant-numeric: tabular-nums; }
        .cd-saved { color: rgb(34 197 94); font-weight: 500; }

        .cd-user-cell {
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }
        .cd-user-avatar {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgb(245 158 11 / 0.18);
            color: rgb(180 83 9);
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .dark .cd-user-avatar { background: rgb(245 158 11 / 0.22); color: rgb(252 211 77); }
        .cd-user-name { font-weight: 500; color: rgb(17 24 39); }
        .dark .cd-user-name { color: rgb(243 244 246); }
        .cd-user-email { font-size: 0.75rem; color: rgb(107 114 128); }
        .cd-user-admin {
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: rgb(180 83 9);
            background: rgb(245 158 11 / 0.15);
            padding: 0.0625rem 0.5rem;
            border-radius: 999px;
            margin-left: auto;
        }
        .dark .cd-user-admin { color: rgb(252 211 77); background: rgb(245 158 11 / 0.22); }

        .cd-empty {
            text-align: center;
            color: rgb(107 114 128);
            padding: 2rem 1rem;
            font-size: 0.875rem;
            margin: 0;
        }
    </style>
</x-filament-panels::page>
