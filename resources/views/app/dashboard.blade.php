<x-layouts.app-shell title="Dashboard — Aarambhax Legal">
    <section class="dash-page">
        <div class="container-page dash-inner">
            {{-- Hero strip --}}
            <header class="dash-hero">
                <div class="min-w-0">
                    <p class="dash-greeting-eyebrow">{{ now()->format('l · d F Y') }}</p>
                    <h1 class="dash-greeting">
                        Welcome back, <span style="color: var(--accent);">{{ explode(' ', auth()->user()->name)[0] }}</span>.
                    </h1>
                    <p class="dash-subgreeting">
                        @if($activeCases > 0)
                            You have {{ $activeCases }} active {{ \Illuminate\Support\Str::plural('case', $activeCases) }}
                            @if($upcomingHearings->isNotEmpty())
                                · next hearing {{ $upcomingHearings->first()->date->diffForHumans() }}
                            @endif
                        @else
                            Open a case or create a new one to get started.
                        @endif
                    </p>
                </div>
                <div class="dash-hero-cta">
                    @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                        <a href="{{ route('app.cases.index') }}" class="dash-btn-primary">
                            Go to cases
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    @else
                        <span class="dash-btn-primary is-disabled" aria-disabled="true">Go to cases</span>
                    @endif
                </div>
            </header>

            {{-- Stat cards --}}
            <div class="dash-stats">
                <div class="dash-stat-card">
                    <p class="dash-stat-label">Active cases</p>
                    <p class="dash-stat-value">{{ $activeCases }}</p>
                </div>
                <div class="dash-stat-card">
                    <p class="dash-stat-label">Drafts in progress</p>
                    <p class="dash-stat-value">{{ $recentDrafts->where('status', '!=', 'finalized')->count() }}</p>
                </div>
                <div class="dash-stat-card">
                    <p class="dash-stat-label">Upcoming hearings</p>
                    <p class="dash-stat-value">{{ $upcomingHearings->count() }}</p>
                </div>
            </div>

            @isset($quotaUsage)
                @php
                    $hourPct = $quotaUsage['per_hour'] > 0 ? min(100, round(($quotaUsage['hour'] / $quotaUsage['per_hour']) * 100)) : 0;
                    $dayPct = $quotaUsage['per_day'] > 0 ? min(100, round(($quotaUsage['day'] / $quotaUsage['per_day']) * 100)) : 0;
                    $hourColor = $hourPct >= 90 ? 'var(--danger)' : ($hourPct >= 70 ? 'var(--warning)' : 'var(--accent)');
                    $dayColor = $dayPct >= 90 ? 'var(--danger)' : ($dayPct >= 70 ? 'var(--warning)' : 'var(--accent)');
                @endphp
                <div class="dash-usage">
                    <div class="dash-usage-header">
                        <p class="dash-stat-label">AI usage</p>
                        @if($quotaUsage['is_admin'])
                            <span class="dash-usage-admin-pill">Admin · unlimited</span>
                        @endif
                    </div>
                    <div class="dash-usage-grid">
                        <div>
                            <div class="dash-usage-row">
                                <span class="dash-usage-label">This hour</span>
                                <span class="dash-usage-num"><strong>{{ $quotaUsage['hour'] }}</strong>/{{ $quotaUsage['per_hour'] }}</span>
                            </div>
                            <div class="dash-bar">
                                <div class="dash-bar-fill" style="width: {{ $hourPct }}%; background: {{ $hourColor }};"></div>
                            </div>
                        </div>
                        <div>
                            <div class="dash-usage-row">
                                <span class="dash-usage-label">Today</span>
                                <span class="dash-usage-num"><strong>{{ $quotaUsage['day'] }}</strong>/{{ $quotaUsage['per_day'] }}</span>
                            </div>
                            <div class="dash-bar">
                                <div class="dash-bar-fill" style="width: {{ $dayPct }}%; background: {{ $dayColor }};"></div>
                            </div>
                        </div>
                    </div>
                    @if(! $quotaUsage['is_admin'] && ($hourPct >= 70 || $dayPct >= 70))
                        <p class="dash-usage-note">
                            @if($hourPct >= 90)
                                Approaching hourly limit. Resets in ~{{ ceil($quotaUsage['hour_reset_seconds'] / 60) }} minutes.
                            @elseif($dayPct >= 90)
                                Approaching daily limit. Resets in ~{{ ceil($quotaUsage['day_reset_seconds'] / 3600) }} hours.
                            @endif
                        </p>
                    @endif
                </div>
            @endisset

            {{-- Recent drafts --}}
            <div class="dash-section">
                <div class="dash-section-head">
                    <h2 class="dash-section-title">Recent drafts</h2>
                    @if($recentDrafts->isNotEmpty())
                        <a href="{{ route('app.drafts.index') }}" class="dash-section-link">All drafts →</a>
                    @endif
                </div>

                @if($recentDrafts->isEmpty())
                    <div class="dash-empty">
                        <div class="dash-empty-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <p class="dash-empty-title">No drafts yet</p>
                        <p class="dash-empty-sub">Drafts are produced by running a Work item on a case.</p>
                        @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                            <a href="{{ route('app.cases.index') }}" class="dash-btn-ghost">Go to cases</a>
                        @endif
                    </div>
                @else
                    <div class="dash-draft-grid">
                        @foreach($recentDrafts as $d)
                            <a href="{{ route('app.drafts.show', $d) }}" class="dash-draft-card">
                                <div class="dash-draft-pills">
                                    <span class="dash-pill dash-pill-accent">{{ str_replace('_', ' ', $d->forum) }}</span>
                                    <span class="dash-pill dash-pill-link">{{ strtoupper($d->language) }}</span>
                                </div>
                                <h3 class="dash-draft-title">{{ \Illuminate\Support\Str::limit($d->title, 70) }}</h3>
                                <p class="dash-draft-meta">{{ $d->updated_at->diffForHumans() }} · {{ $d->status }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <style>
        /* ── Dashboard premium polish ──────────────────────────────────── */
        .dash-page {
            background: linear-gradient(180deg,
                color-mix(in srgb, var(--accent) 4%, var(--bg)) 0%,
                var(--bg) 320px);
            min-height: 100vh;
        }
        .dash-inner {
            padding-top: 2.5rem;
            padding-bottom: 4rem;
            max-width: 1100px;
        }

        /* Hero */
        .dash-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }
        .dash-greeting-eyebrow {
            font-size: 0.75rem;
            color: var(--fg-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 0 0 0.5rem;
        }
        .dash-greeting {
            font-family: var(--font-serif);
            font-size: clamp(2rem, 4vw, 2.875rem);
            font-weight: 500;
            color: var(--fg);
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin: 0;
        }
        .dash-subgreeting {
            margin: 0.625rem 0 0;
            font-size: 0.9375rem;
            color: var(--fg-muted);
            line-height: 1.5;
            max-width: 60ch;
        }

        .dash-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.375rem;
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            font-weight: 500;
            background: var(--primary);
            color: var(--primary-fg);
            border: 1px solid var(--primary);
            border-radius: 9px;
            text-decoration: none;
            transition: transform 120ms ease-out, box-shadow 220ms ease-out;
        }
        .dash-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px -8px color-mix(in srgb, var(--primary) 40%, transparent);
        }
        .dash-btn-primary:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }
        .dash-btn-primary.is-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .dash-btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.125rem;
            font-size: 0.875rem;
            font-weight: 500;
            background: transparent;
            color: var(--fg);
            border: 1px solid var(--border);
            border-radius: 8px;
            text-decoration: none;
            transition: background 150ms ease-out;
        }
        .dash-btn-ghost:hover {
            background: color-mix(in srgb, var(--fg) 5%, transparent);
        }

        /* Stat cards */
        .dash-stats {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.875rem;
            margin-bottom: 1rem;
        }
        @media (min-width: 720px) {
            .dash-stats { grid-template-columns: repeat(3, 1fr); }
        }

        .dash-stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem 1.5rem;
            transition: transform 220ms ease-out, box-shadow 220ms ease-out;
        }
        .dash-stat-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px -10px color-mix(in srgb, var(--primary) 18%, transparent);
        }
        .dash-stat-label {
            font-size: 0.6875rem;
            color: var(--fg-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            margin: 0;
        }
        .dash-stat-value {
            font-family: var(--font-serif);
            font-size: 2.25rem;
            font-weight: 500;
            color: var(--fg);
            margin: 0.625rem 0 0;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        /* Usage card */
        .dash-usage {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem 1.5rem 1.625rem;
            margin-bottom: 2.5rem;
        }
        .dash-usage-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.125rem;
        }
        .dash-usage-admin-pill {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            padding: 0.1875rem 0.625rem;
            border-radius: 999px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .dash-usage-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.125rem;
        }
        @media (min-width: 720px) {
            .dash-usage-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
        }
        .dash-usage-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 0.4rem;
        }
        .dash-usage-label {
            font-size: 0.75rem;
            color: var(--fg-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dash-usage-num {
            font-family: var(--font-mono, ui-monospace), monospace;
            font-size: 0.8125rem;
            color: var(--fg-muted);
            font-variant-numeric: tabular-nums;
        }
        .dash-usage-num strong {
            color: var(--fg);
            font-weight: 600;
        }
        .dash-bar {
            height: 6px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--border) 80%, transparent);
            overflow: hidden;
        }
        .dash-bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 600ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        .dash-usage-note {
            font-size: 0.8125rem;
            color: var(--fg-muted);
            margin: 1rem 0 0;
        }

        /* Sections */
        .dash-section { margin-bottom: 2.5rem; }
        .dash-section-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .dash-section-title {
            font-family: var(--font-serif);
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--fg);
            margin: 0;
            letter-spacing: -0.012em;
        }
        .dash-section-link {
            font-size: 0.8125rem;
            color: var(--fg-muted);
            text-decoration: none;
            transition: color 150ms ease-out;
        }
        .dash-section-link:hover { color: var(--fg); }

        /* Empty state */
        .dash-empty {
            background: var(--surface);
            border: 1px dashed color-mix(in srgb, var(--accent) 28%, var(--border));
            border-radius: 14px;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .dash-empty-icon {
            display: inline-flex;
            color: var(--accent);
            opacity: 0.7;
            margin-bottom: 0.875rem;
        }
        .dash-empty-title {
            font-family: var(--font-serif);
            font-size: 1.1875rem;
            color: var(--fg);
            font-weight: 500;
            margin: 0 0 0.375rem;
        }
        .dash-empty-sub {
            font-size: 0.875rem;
            color: var(--fg-muted);
            margin: 0 0 1.25rem;
            max-width: 50ch;
            margin-left: auto;
            margin-right: auto;
        }

        /* Draft cards */
        .dash-draft-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.875rem;
        }
        @media (min-width: 720px) {
            .dash-draft-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1000px) {
            .dash-draft-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .dash-draft-card {
            display: block;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: border-color 220ms ease-out, transform 220ms ease-out, box-shadow 220ms ease-out;
        }
        .dash-draft-card:hover {
            border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
            transform: translateY(-2px);
            box-shadow: 0 8px 22px -12px color-mix(in srgb, var(--primary) 25%, transparent);
        }
        .dash-draft-pills {
            display: flex;
            gap: 0.375rem;
            margin-bottom: 0.625rem;
            flex-wrap: wrap;
        }
        .dash-pill {
            display: inline-flex;
            align-items: center;
            font-size: 0.6875rem;
            padding: 0.1875rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .dash-pill-accent {
            background: color-mix(in srgb, var(--accent) 14%, transparent);
            color: var(--accent);
        }
        .dash-pill-link {
            background: color-mix(in srgb, var(--link) 12%, transparent);
            color: var(--link);
        }
        .dash-draft-title {
            font-family: var(--font-serif);
            font-size: 1.0625rem;
            font-weight: 500;
            color: var(--fg);
            margin: 0 0 0.4rem;
            letter-spacing: -0.005em;
            line-height: 1.35;
        }
        .dash-draft-meta {
            font-size: 0.75rem;
            color: var(--fg-muted);
            margin: 0;
        }

        @media (prefers-reduced-motion: reduce) {
            .dash-stat-card,
            .dash-draft-card,
            .dash-bar-fill,
            .dash-btn-primary { transition: none; }
            .dash-stat-card:hover,
            .dash-draft-card:hover,
            .dash-btn-primary:hover { transform: none; }
        }
    </style>
</x-layouts.app-shell>
