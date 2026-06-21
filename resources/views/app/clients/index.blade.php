<x-layouts.app-shell title="Clients — Aarambhax">
    <section class="cases-page">
        <div class="container-page cases-inner">
            <header class="cases-header">
                <div>
                    <p class="cases-eyebrow">Workspace</p>
                    <h1 class="cases-title">Clients</h1>
                </div>
                @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                    <a href="{{ route('app.clients.create') }}" class="cases-btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        New client
                    </a>
                @else
                    <span class="cases-btn-primary is-disabled" aria-disabled="true">+ New client</span>
                @endif
            </header>

            @if($clients->isEmpty())
                <div class="cases-empty">
                    <div class="cases-empty-icon">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <p class="cases-empty-title">No clients yet</p>
                    <p class="cases-empty-sub">Add a client to organize their cases together.</p>
                    @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                        <a href="{{ route('app.clients.create') }}" class="cases-btn-primary">Add your first client</a>
                    @endif
                </div>
            @else
                <div class="clients-grid">
                    @foreach($clients as $c)
                        <a href="{{ route('app.clients.show', $c) }}" class="client-card">
                            <div class="client-card-avatar">{{ strtoupper(substr($c->name, 0, 1)) }}</div>
                            <h2 class="client-card-name">{{ $c->name }}</h2>
                            <div class="client-card-contacts">
                                @if($c->phone)
                                    <div class="client-card-line">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        <span>{{ $c->phone }}</span>
                                    </div>
                                @endif
                                @if($c->email)
                                    <div class="client-card-line">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                        <span>{{ $c->email }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="client-card-foot">
                                <span class="client-card-cases">{{ $c->cases_count }} {{ $c->cases_count === 1 ? 'case' : 'cases' }}</span>
                                <span class="client-card-arrow">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="cases-pagination">{{ $clients->links() }}</div>
            @endif
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
        .cases-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--primary) 35%, transparent);
        }
        .cases-btn-primary.is-disabled { opacity: 0.5; cursor: not-allowed; }

        /* Client cards */
        .clients-grid {
            display: grid; grid-template-columns: 1fr; gap: 0.875rem;
        }
        @media (min-width: 720px) { .clients-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1000px) { .clients-grid { grid-template-columns: repeat(3, 1fr); } }

        .client-card {
            display: block; padding: 1.5rem;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; text-decoration: none; color: inherit;
            transition: border-color 220ms ease-out, transform 220ms ease-out, box-shadow 220ms ease-out;
        }
        .client-card:hover {
            border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
            transform: translateY(-1px);
            box-shadow: 0 6px 22px -10px color-mix(in srgb, var(--primary) 22%, transparent);
        }
        .client-card-avatar {
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px;
            background: color-mix(in srgb, var(--accent) 15%, transparent);
            color: var(--accent);
            font-family: var(--font-serif);
            font-size: 1.0625rem; font-weight: 600;
            border-radius: 10px; margin-bottom: 0.875rem;
        }
        .client-card-name {
            font-family: var(--font-serif); font-size: 1.125rem; font-weight: 500;
            color: var(--fg); margin: 0; letter-spacing: -0.005em;
        }
        .client-card-contacts {
            margin-top: 0.625rem; display: flex; flex-direction: column; gap: 0.25rem;
        }
        .client-card-line {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.8125rem; color: var(--fg-muted);
        }
        .client-card-line svg { color: var(--fg-muted); flex-shrink: 0; }
        .client-card-foot {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 1.125rem; padding-top: 0.875rem;
            border-top: 1px solid var(--border);
        }
        .client-card-cases {
            font-size: 0.75rem; color: var(--fg-muted);
            text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;
        }
        .client-card-arrow {
            color: var(--fg-muted); font-size: 1.125rem;
            transition: transform 200ms ease-out, color 200ms ease-out;
        }
        .client-card:hover .client-card-arrow { transform: translateX(3px); color: var(--accent); }

        /* Empty + pagination */
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
            .client-card, .cases-btn-primary, .client-card-arrow { transition: none; }
            .client-card:hover, .cases-btn-primary:hover { transform: none; }
        }
    </style>
</x-layouts.app-shell>
