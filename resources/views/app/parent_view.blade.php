<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $student->name }} — Parent Dashboard</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0d1117; color: #e6edf3; min-height: 100vh; }

        .pv-wrap { max-width: 600px; margin: 0 auto; padding: 2rem 1rem 4rem; }

        /* Header */
        .pv-header { margin-bottom: 2rem; }
        .pv-brand { font-size: 0.75rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #f59e0b; margin-bottom: 1rem; }
        .pv-name  { font-size: 1.75rem; font-weight: 700; color: #e6edf3; line-height: 1.2; }
        .pv-sub   { font-size: 0.875rem; color: #7d8590; margin-top: 0.375rem; }

        /* Today badge */
        .pv-today { background: #161b22; border: 1px solid #30363d; border-radius: 14px; padding: 1.25rem; margin-bottom: 1.5rem; }
        .pv-today-head { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #7d8590; margin-bottom: 0.875rem; }
        .pv-today-rows { display: flex; flex-direction: column; gap: 0.5rem; }
        .pv-today-row  { display: flex; justify-content: space-between; align-items: center; font-size: 0.9375rem; }
        .pv-today-key  { color: #7d8590; }
        .pv-today-val  { font-weight: 600; color: #e6edf3; }
        .pv-no-log     { font-size: 0.875rem; color: #7d8590; text-align: center; padding: 0.5rem 0; }

        /* Stats row */
        .pv-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
        .pv-stat  { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 1rem; }
        .pv-stat-val  { font-size: 1.5rem; font-weight: 700; color: #e6edf3; line-height: 1; }
        .pv-stat-label { font-size: 0.75rem; color: #7d8590; margin-top: 0.25rem; }

        /* Mood bar */
        .pv-mood-bar { height: 6px; border-radius: 99px; background: #21262d; overflow: hidden; margin-top: 0.5rem; }
        .pv-mood-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #ef4444, #f59e0b, #22c55e); transition: width 0.6s ease; }

        /* 14-day activity */
        .pv-section-head { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #7d8590; margin-bottom: 0.75rem; }
        .pv-days { display: flex; gap: 0.375rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .pv-day  { width: 36px; text-align: center; }
        .pv-day-dot { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; margin-bottom: 0.25rem; }
        .pv-day-label { font-size: 0.5625rem; color: #7d8590; }
        .pv-day-active   { background: color-mix(in srgb, #22c55e 20%, #161b22); border: 1px solid color-mix(in srgb, #22c55e 35%, #30363d); }
        .pv-day-inactive { background: #161b22; border: 1px solid #30363d; color: #7d8590; }

        /* Weak topics */
        .pv-weak { background: #161b22; border: 1px solid color-mix(in srgb, #ef4444 30%, #30363d); border-radius: 14px; padding: 1.25rem; margin-bottom: 1.5rem; }
        .pv-weak-head { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #ef4444; margin-bottom: 0.875rem; }
        .pv-weak-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .pv-weak-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #e6edf3; }
        .pv-weak-dot  { width: 6px; height: 6px; border-radius: 50%; background: #ef4444; flex-shrink: 0; }
        .pv-no-weak   { font-size: 0.875rem; color: #7d8590; }

        /* Footer */
        .pv-footer { text-align: center; font-size: 0.75rem; color: #7d8590; margin-top: 2rem; }
        .pv-footer span { color: #f59e0b; font-weight: 600; }
    </style>
</head>
<body>
<div class="pv-wrap">

    <div class="pv-header">
        <p class="pv-brand">Shrutam · Parent View</p>
        <h1 class="pv-name">{{ $student->name }}</h1>
        <p class="pv-sub">{{ now()->format('l, d F Y') }}</p>
    </div>

    {{-- Today's summary --}}
    <div class="pv-today">
        <p class="pv-today-head">Aaj ka update</p>
        @if($todayLog)
            <div class="pv-today-rows">
                @if($todayLog->studied_topics)
                    <div class="pv-today-row">
                        <span class="pv-today-key">Kya padha</span>
                        <span class="pv-today-val">{{ $todayLog->studied_topics }}</span>
                    </div>
                @endif
                @if($todayLog->hours_studied)
                    <div class="pv-today-row">
                        <span class="pv-today-key">Study hours</span>
                        <span class="pv-today-val">{{ $todayLog->hours_studied }} hrs</span>
                    </div>
                @endif
                @if($todayLog->food)
                    <div class="pv-today-row">
                        <span class="pv-today-key">Khaana</span>
                        <span class="pv-today-val">{{ $todayLog->food }}</span>
                    </div>
                @endif
                @if($todayLog->expenses)
                    <div class="pv-today-row">
                        <span class="pv-today-key">Kharch</span>
                        <span class="pv-today-val">₹{{ number_format($todayLog->expenses, 0) }}</span>
                    </div>
                @endif
                @if($todayLog->mood)
                    <div class="pv-today-row">
                        <span class="pv-today-key">Mood</span>
                        <span class="pv-today-val" style="font-size:1.25rem">{{ $todayLog->moodEmoji() }}</span>
                    </div>
                @endif
            </div>
        @else
            <p class="pv-no-log">Aaj ka log abhi nahi bhara — {{ explode(' ', $student->name)[0] }} se poochho 📲</p>
        @endif
    </div>

    {{-- Stats --}}
    <div class="pv-stats">
        <div class="pv-stat">
            <div class="pv-stat-val">{{ $studyDays }}<span style="font-size:0.875rem;color:#7d8590">/14</span></div>
            <div class="pv-stat-label">Study days (last 2 weeks)</div>
        </div>
        <div class="pv-stat">
            <div class="pv-stat-val">{{ number_format($totalHours, 1) }}<span style="font-size:0.875rem;color:#7d8590"> hrs</span></div>
            <div class="pv-stat-label">Total study time</div>
        </div>
        <div class="pv-stat">
            <div class="pv-stat-val">₹{{ number_format($totalExpense, 0) }}</div>
            <div class="pv-stat-label">Total expenses (14 days)</div>
        </div>
        <div class="pv-stat">
            <div class="pv-stat-val">{{ $noteCount }}</div>
            <div class="pv-stat-label">Notes uploaded</div>
        </div>
    </div>

    {{-- Mood trend --}}
    @if($avgMood)
    <p class="pv-section-head">Average mood (14 days)</p>
    <div style="margin-bottom:1.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.375rem">
            <span style="font-size:1.25rem">{{ (new App\Models\DailyLog(['mood' => round($avgMood)]))->moodEmoji() }}</span>
            <span style="font-size:0.875rem;color:#7d8590">{{ number_format($avgMood, 1) }} / 5</span>
        </div>
        <div class="pv-mood-bar">
            <div class="pv-mood-fill" style="width:{{ ($avgMood / 5) * 100 }}%"></div>
        </div>
    </div>
    @endif

    {{-- 14-day activity grid --}}
    @php
        $logMap = $logs->keyBy(fn($l) => $l->log_date->format('Y-m-d'));
        $days14 = collect(range(13, 0))->map(fn($i) => now()->subDays($i));
    @endphp
    <p class="pv-section-head">14-day activity</p>
    <div class="pv-days">
        @foreach($days14 as $day)
            @php $log = $logMap->get($day->format('Y-m-d')); @endphp
            <div class="pv-day">
                <div class="pv-day-dot {{ $log && $log->hours_studied > 0 ? 'pv-day-active' : 'pv-day-inactive' }}">
                    {{ $log ? $log->moodEmoji() : '—' }}
                </div>
                <div class="pv-day-label">{{ $day->format('d') }}</div>
            </div>
        @endforeach
    </div>

    {{-- Weak topics --}}
    @if($weakTopics->isNotEmpty())
    <div class="pv-weak">
        <p class="pv-weak-head">Weak areas (needs attention)</p>
        <div class="pv-weak-list">
            @foreach($weakTopics as $topic)
                <div class="pv-weak-item">
                    <span class="pv-weak-dot"></span>
                    {{ $topic }}
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="pv-footer">
        Powered by <span>Shrutam</span> · This link is private — only share with family
    </div>

</div>
</body>
</html>
