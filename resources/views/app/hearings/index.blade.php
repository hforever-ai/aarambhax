<x-layouts.app-shell title="Hearings — Aarambhax">
    <section class="cases-page">
        <div class="container-page cases-inner">
            <header class="cases-header">
                <div>
                    <p class="cases-eyebrow">Workspace</p>
                    <h1 class="cases-title">Hearings</h1>
                </div>
                @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                    <a href="{{ route('app.hearings.create') }}" class="cases-btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add hearing
                    </a>
                @else
                    <span class="cases-btn-primary is-disabled" aria-disabled="true">+ Add hearing</span>
                @endif
            </header>

            {{-- Upcoming --}}
            <section class="hearings-section">
                <h2 class="hearings-section-title">Upcoming</h2>
                @if($upcoming->isEmpty())
                    <div class="cases-empty" style="padding: 1.75rem 1.25rem;">
                        <p class="cases-empty-sub" style="margin-bottom: 0;">No upcoming hearings.</p>
                    </div>
                @else
                    <ul class="hearings-list" role="list">
                        @foreach($upcoming as $h)
                            <li>
                                <a href="{{ route('app.cases.show', $h->case_id) }}" class="hearing-row">
                                    <div class="hearing-date hearing-date-upcoming">
                                        <span class="hearing-date-day">{{ $h->date->format('d') }}</span>
                                        <span class="hearing-date-mon">{{ $h->date->format('M') }}</span>
                                    </div>
                                    <div class="hearing-row-main">
                                        <div class="hearing-row-title">{{ $h->case?->title ?: 'Untitled case' }}</div>
                                        <div class="hearing-row-meta">
                                            <span>{{ $h->time ?: 'Time TBD' }}</span>
                                            <span class="cases-row-sep">·</span>
                                            <span>{{ $h->purpose ?: 'No purpose noted' }}</span>
                                            @if($h->court)
                                                <span class="cases-row-sep">·</span>
                                                <span>{{ $h->court }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="hearing-row-status">
                                        @if($h->reminded_at)
                                            <span class="hearing-status-pill hearing-status-reminded">Reminded</span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Recent --}}
            <section class="hearings-section">
                <h2 class="hearings-section-title">Recent (last 20)</h2>
                @if($past->isEmpty())
                    <div class="cases-empty" style="padding: 1.75rem 1.25rem;">
                        <p class="cases-empty-sub" style="margin-bottom: 0;">No past hearings logged.</p>
                    </div>
                @else
                    <ul class="hearings-list" role="list">
                        @foreach($past as $h)
                            <li>
                                <a href="{{ route('app.cases.show', $h->case_id) }}" class="hearing-row hearing-row-past">
                                    <div class="hearing-date">
                                        <span class="hearing-date-day">{{ $h->date->format('d') }}</span>
                                        <span class="hearing-date-mon">{{ $h->date->format('M') }}</span>
                                    </div>
                                    <div class="hearing-row-main">
                                        <div class="hearing-row-title">{{ $h->case?->title ?? 'Untitled case' }}</div>
                                        <div class="hearing-row-meta">
                                            <span>{{ $h->date->format('Y') }}</span>
                                            @if($h->purpose)
                                                <span class="cases-row-sep">·</span>
                                                <span>{{ $h->purpose }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="hearing-row-status">
                                        @if($h->outcome)
                                            <span class="hearing-status-pill hearing-status-outcome">{{ \Illuminate\Support\Str::limit($h->outcome, 32) }}</span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </section>

    <style>
        .cases-page {
            background: linear-gradient(180deg,
                color-mix(in srgb, var(--accent) 3%, var(--bg)) 0%,
                var(--bg) 240px);
            min-height: 100vh;
        }
        .cases-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 1100px; }
        .cases-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 1.25rem; margin-bottom: 2.25rem; flex-wrap: wrap;
        }
        .cases-eyebrow {
            font-size: 0.75rem; color: var(--fg-muted);
            text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 0.4rem;
        }
        .cases-title {
            font-family: var(--font-serif);
            font-size: clamp(2rem, 3.5vw, 2.625rem); font-weight: 500;
            color: var(--fg); letter-spacing: -0.018em; line-height: 1.1; margin: 0;
        }
        .cases-btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.6875rem 1.25rem; font-size: 0.9375rem; font-weight: 500;
            background: var(--primary); color: var(--primary-fg);
            border: 1px solid var(--primary); border-radius: 9px;
            text-decoration: none;
            transition: transform 120ms ease-out, box-shadow 220ms ease-out;
        }
        .cases-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--primary) 35%, transparent); }
        .cases-btn-primary.is-disabled { opacity: 0.5; cursor: not-allowed; }
        .cases-row-sep { color: color-mix(in srgb, var(--border) 60%, transparent); }

        .hearings-section { margin-bottom: 2.25rem; }
        .hearings-section-title {
            font-family: var(--font-serif); font-size: 1.0625rem; font-weight: 500;
            color: var(--fg-muted); margin: 0 0 0.875rem;
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .hearings-list {
            list-style: none; padding: 0; margin: 0;
            display: flex; flex-direction: column; gap: 0.5rem;
        }
        .hearing-row {
            display: flex; align-items: center; gap: 1.125rem;
            padding: 1rem 1.25rem;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; text-decoration: none; color: inherit;
            transition: border-color 220ms ease-out, transform 220ms ease-out, box-shadow 220ms ease-out;
        }
        .hearing-row:hover {
            border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
            transform: translateY(-1px);
            box-shadow: 0 6px 18px -10px color-mix(in srgb, var(--primary) 22%, transparent);
        }
        .hearing-row-past { opacity: 0.85; }
        .hearing-row-past:hover { opacity: 1; }

        .hearing-date {
            flex-shrink: 0; text-align: center; min-width: 56px;
            background: color-mix(in srgb, var(--fg-muted) 6%, var(--surface));
            border: 1px solid var(--border);
            border-radius: 10px; padding: 0.4rem 0.5rem;
        }
        .hearing-date-upcoming {
            background: color-mix(in srgb, var(--accent) 8%, var(--surface));
            border-color: color-mix(in srgb, var(--accent) 30%, var(--border));
        }
        .hearing-date-day {
            display: block; font-family: var(--font-serif); font-size: 1.375rem;
            font-weight: 600; color: var(--fg); line-height: 1;
        }
        .hearing-date-upcoming .hearing-date-day { color: var(--accent); }
        .hearing-date-mon {
            display: block; font-size: 0.6875rem; color: var(--fg-muted);
            text-transform: uppercase; letter-spacing: 0.06em; margin-top: 0.25rem;
        }
        .hearing-row-main { flex: 1; min-width: 0; }
        .hearing-row-title {
            font-family: var(--font-serif); font-size: 1rem; font-weight: 500;
            color: var(--fg); margin: 0; letter-spacing: -0.005em;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .hearing-row-meta {
            display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;
            font-size: 0.8125rem; color: var(--fg-muted); margin-top: 0.25rem;
        }
        .hearing-row-status { flex-shrink: 0; }
        .hearing-status-pill {
            font-size: 0.6875rem; padding: 0.1875rem 0.625rem;
            border-radius: 999px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        .hearing-status-reminded {
            background: color-mix(in srgb, var(--success) 12%, transparent);
            color: var(--success);
        }
        .hearing-status-outcome {
            background: color-mix(in srgb, var(--fg-muted) 10%, transparent);
            color: var(--fg-muted);
            text-transform: none; letter-spacing: 0;
            font-weight: 500;
        }

        .cases-empty {
            background: var(--surface);
            border: 1px dashed color-mix(in srgb, var(--accent) 28%, var(--border));
            border-radius: 12px; padding: 2rem 1.5rem; text-align: center;
        }
        .cases-empty-sub {
            font-size: 0.875rem; color: var(--fg-muted);
            margin: 0; line-height: 1.55;
        }

        @media (prefers-reduced-motion: reduce) {
            .hearing-row, .cases-btn-primary { transition: none; }
            .hearing-row:hover, .cases-btn-primary:hover { transform: none; }
        }
    </style>
</x-layouts.app-shell>
