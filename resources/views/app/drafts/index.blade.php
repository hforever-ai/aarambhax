<x-layouts.app-shell title="Your drafts — Aarambhax">
    <section class="cases-page">
        <div class="container-page cases-inner">
            <header class="cases-header">
                <div>
                    <p class="cases-eyebrow">Workspace</p>
                    <h1 class="cases-title">Your drafts</h1>
                    <p class="cases-subtitle">Drafts are produced by running a Work item on a case.</p>
                </div>
                @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                    <a href="{{ route('app.cases.index') }}" class="cases-btn-primary">
                        Go to cases
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                @else
                    <span class="cases-btn-primary is-disabled" aria-disabled="true">Go to cases</span>
                @endif
            </header>

            @if($drafts->isEmpty())
                <div class="cases-empty">
                    <div class="cases-empty-icon">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <p class="cases-empty-title">No drafts yet</p>
                    <p class="cases-empty-sub">Open a case and run a Work item from the Draft tab — drafts will appear here.</p>
                    @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                        <a href="{{ route('app.cases.index') }}" class="cases-btn-primary">Go to cases</a>
                    @endif
                </div>
            @else
                <div class="cases-list">
                    @foreach($drafts as $d)
                        <a href="{{ route('app.drafts.show', $d) }}" class="cases-row">
                            <div class="cases-row-main">
                                <div class="cases-row-title-line">
                                    <h3 class="cases-row-title">{{ \Illuminate\Support\Str::limit($d->title, 80) }}</h3>
                                    <span class="cases-status cases-status-{{ $d->status === 'finalized' ? 'active' : 'closed' }}">{{ ucfirst($d->status) }}</span>
                                </div>
                                <div class="cases-row-meta">
                                    @if($d->forum)
                                        <span>{{ str_replace('_', ' ', strtoupper($d->forum)) }}</span>
                                    @endif
                                    @if($d->draft_type)
                                        <span class="cases-row-sep">·</span>
                                        <span>{{ $d->draft_type }}</span>
                                    @endif
                                    <span class="cases-row-sep">·</span>
                                    <span>{{ strtoupper($d->language) }}</span>
                                </div>
                            </div>
                            <div class="cases-row-side">
                                <div class="cases-row-hearing">
                                    <span class="cases-row-hearing-label">Updated</span>
                                    <span class="cases-row-hearing-date">{{ $d->updated_at->diffForHumans() }}</span>
                                </div>
                                <span class="cases-row-arrow">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="cases-pagination">{{ $drafts->links() }}</div>
            @endif
        </div>
    </section>

    <style>
        /* Same premium list pattern as /app/cases — inlined for self-containment */
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
            text-transform: uppercase; letter-spacing: 0.08em;
            margin: 0 0 0.4rem;
        }
        .cases-title {
            font-family: var(--font-serif);
            font-size: clamp(2rem, 3.5vw, 2.625rem); font-weight: 500;
            color: var(--fg); letter-spacing: -0.018em; line-height: 1.1; margin: 0;
        }
        .cases-subtitle {
            font-size: 0.9375rem; color: var(--fg-muted);
            margin: 0.625rem 0 0; max-width: 56ch; line-height: 1.55;
        }

        .cases-btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.6875rem 1.25rem; font-size: 0.9375rem; font-weight: 500;
            background: var(--primary); color: var(--primary-fg);
            border: 1px solid var(--primary); border-radius: 9px;
            text-decoration: none;
            transition: transform 120ms ease-out, box-shadow 220ms ease-out;
        }
        .cases-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--primary) 35%, transparent);
        }
        .cases-btn-primary:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
        .cases-btn-primary.is-disabled { opacity: 0.5; cursor: not-allowed; }

        .cases-list { display: flex; flex-direction: column; gap: 0.625rem; }
        .cases-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1.5rem; padding: 1.25rem 1.5rem;
            background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
            text-decoration: none; color: inherit;
            transition: border-color 220ms ease-out, transform 220ms ease-out, box-shadow 220ms ease-out;
        }
        .cases-row:hover {
            border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
            transform: translateY(-1px);
            box-shadow: 0 6px 22px -10px color-mix(in srgb, var(--primary) 22%, transparent);
        }
        .cases-row:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .cases-row-main { flex: 1; min-width: 0; }
        .cases-row-title-line {
            display: flex; align-items: center; gap: 0.75rem;
            margin-bottom: 0.4rem; flex-wrap: wrap;
        }
        .cases-row-title {
            font-family: var(--font-serif); font-size: 1.125rem; font-weight: 500;
            color: var(--fg); margin: 0; letter-spacing: -0.005em; line-height: 1.3;
        }
        .cases-row-meta {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.8125rem; color: var(--fg-muted); flex-wrap: wrap;
        }
        .cases-row-sep { color: color-mix(in srgb, var(--border) 60%, transparent); }
        .cases-row-side { display: flex; align-items: center; gap: 1.25rem; flex-shrink: 0; }
        .cases-row-hearing { display: flex; flex-direction: column; text-align: right; line-height: 1.2; }
        .cases-row-hearing-label {
            font-size: 0.6875rem; color: var(--fg-muted);
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .cases-row-hearing-date {
            font-family: var(--font-serif); font-size: 0.9375rem;
            color: var(--fg); font-weight: 500; margin-top: 0.125rem;
        }
        .cases-row-arrow {
            color: var(--fg-muted); font-size: 1.25rem;
            transition: transform 200ms ease-out, color 200ms ease-out;
        }
        .cases-row:hover .cases-row-arrow { transform: translateX(3px); color: var(--accent); }

        .cases-status {
            display: inline-flex; align-items: center; gap: 0.3rem;
            font-size: 0.6875rem; padding: 0.1875rem 0.625rem;
            border-radius: 999px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .cases-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .cases-status-active { background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success); }
        .cases-status-closed { background: color-mix(in srgb, var(--fg-muted) 12%, transparent); color: var(--fg-muted); }

        .cases-empty {
            background: var(--surface);
            border: 1px dashed color-mix(in srgb, var(--accent) 28%, var(--border));
            border-radius: 16px; padding: 3rem 2rem; text-align: center;
        }
        .cases-empty-icon { display: inline-flex; color: var(--accent); opacity: 0.7; margin-bottom: 1rem; }
        .cases-empty-title {
            font-family: var(--font-serif); font-size: 1.3125rem;
            color: var(--fg); font-weight: 500; margin: 0 0 0.5rem;
        }
        .cases-empty-sub {
            font-size: 0.9375rem; color: var(--fg-muted);
            margin: 0 auto 1.5rem; max-width: 50ch; line-height: 1.55;
        }
        .cases-pagination { margin-top: 1.5rem; }

        @media (prefers-reduced-motion: reduce) {
            .cases-row, .cases-btn-primary, .cases-row-arrow { transition: none; }
            .cases-row:hover, .cases-btn-primary:hover { transform: none; }
        }
    </style>
</x-layouts.app-shell>
