@php
    // Tab strip — ordered by daily-use frequency for chambers practice.
    // Bahas first (hearing prep is the daily morning ritual), then Documents,
    // then chat-style work modes, then artefact tabs (Analysis, Draft),
    // then Details (least-frequently visited metadata).
    $tabs = [
        'bahas' => [
            'label' => 'Bahas',
            'route' => 'app.cases.tab.bahas',
            'badge' => null,
            'icon'  => '<path d="M3 21h18"/><path d="M5 21V7l8-4 8 4v14"/><path d="M9 9h6M9 13h6M9 17h4"/>',
        ],
        'documents' => [
            'label' => 'Documents',
            'route' => 'app.cases.tab.documents',
            'badge' => $case->documents_count,
            'icon'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        ],
        'brainstorm' => [
            'label' => 'Brainstorm',
            'route' => 'app.cases.tab.brainstorm',
            'badge' => null,
            'icon'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        ],
        'cross_exam' => [
            'label' => 'Cross-Exam',
            'route' => 'app.cases.tab.cross_exam',
            'badge' => null,
            'icon'  => '<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>',
        ],
        'draft' => [
            'label' => 'Draft',
            'route' => 'app.cases.tab.draft',
            'badge' => null,
            'icon'  => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        ],
        'notes' => [
            'label' => 'Notes',
            'route' => 'app.cases.tab.notes',
            'badge' => null,
            'icon'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        ],
        'details' => [
            'label' => 'Details',
            'route' => 'app.cases.tab.details',
            'badge' => null,
            'icon'  => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        ],
    ];
@endphp

<x-layouts.app-shell title="{{ $case->title }} — Aarambhax Legal">
    <section class="case-shell">
        {{-- Persistent case header — single compact row + tabs below --}}
        <header class="case-header">
            <div class="container-page">
                <div class="case-header-row">
                    <a href="{{ route('app.cases.index') }}" class="case-back" aria-label="Back to all cases">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                    <h1 class="case-title">{{ $case->title }}</h1>
                    <div class="case-meta">
                        @if($case->forum)
                            <span>{{ str_replace('_', ' ', strtoupper($case->forum)) }}</span>
                        @endif
                        @if($case->case_no)
                            <span class="case-meta-sep">·</span>
                            <span class="font-mono">{{ $case->case_no }}</span>
                        @endif
                        @if($case->client)
                            <span class="case-meta-sep">·</span>
                            <span>{{ $case->client->name }}</span>
                        @endif
                        <span class="case-status-badge case-status-{{ $case->status }}">{{ ucfirst($case->status) }}</span>
                    </div>
                </div>

                {{-- Underline tabs — Linear / Vercel / GitHub pattern for entity sub-sections --}}
                <nav class="case-tabs" aria-label="Case sections">
                    @foreach($tabs as $key => $tab)
                        @php($isActive = $activeTab === $key)
                        <a
                            href="{{ route($tab['route'], $case) }}"
                            class="case-tab {{ $isActive ? 'is-active' : '' }}"
                            @if($isActive) aria-current="page" @endif
                        >
                            <span class="case-tab-label">{{ $tab['label'] }}</span>
                            @if($tab['badge'] !== null && $tab['badge'] > 0)
                                <span class="case-tab-count">{{ $tab['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        {{-- Tab content --}}
        <main class="case-tab-content" role="tabpanel" id="case-tab-{{ $activeTab }}">
            <div class="container-page case-tab-inner">
                @switch($activeTab)
                    @case('details')
                        @include('app.cases.tabs.details', ['case' => $case, 'hearings' => $hearings ?? collect()])
                        @break
                    @case('documents')
                        @include('app.cases.tabs.documents', ['case' => $case, 'documents' => $documents ?? collect()])
                        @break
                    @case('draft')
                        @include('app.cases.tabs.draft', ['case' => $case, 'documents' => $documents ?? collect(), 'conversation' => $conversation, 'messages' => $messages ?? collect(), 'pastWorks' => $pastWorks ?? collect(), 'drafts' => $drafts ?? collect()])
                        @break
                    @case('brainstorm')
                        @include('app.cases.tabs.brainstorm', ['case' => $case, 'documents' => $documents ?? collect(), 'conversation' => $conversation, 'messages' => $messages ?? collect()])
                        @break
                    @case('bahas')
                        @include('app.cases.tabs.bahas', ['case' => $case, 'documents' => $documents ?? collect(), 'conversation' => $conversation, 'messages' => $messages ?? collect()])
                        @break
                    @case('cross_exam')
                        @include('app.cases.tabs.cross_exam', ['case' => $case, 'documents' => $documents ?? collect(), 'conversation' => $conversation, 'messages' => $messages ?? collect()])
                        @break
                    @case('notes')
                        @include('app.cases.tabs.notes', ['case' => $case, 'documents' => $documents ?? collect(), 'conversation' => $conversation, 'messages' => $messages ?? collect()])
                        @break
                @endswitch
            </div>
        </main>
    </section>

    <style>
        /* ── Premium case shell — refined ──────────────────────────────── */

        .case-shell {
            --case-pad-y: 0.625rem;        /* 10px top */
        }

        .case-header {
            background: var(--bg);
            padding-top: var(--case-pad-y);
            border-bottom: 1px solid var(--border);
        }

        /* Single-row header: ‹ Title · meta-pills … */
        .case-header-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;         /* 8px to tabs */
            flex-wrap: nowrap;
            min-height: 38px;
        }

        .case-back {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            color: var(--fg-muted);
            border-radius: 6px;
            text-decoration: none;
            transition: background 150ms ease-out, color 150ms ease-out;
        }
        .case-back:hover {
            background: color-mix(in srgb, var(--fg) 5%, transparent);
            color: var(--fg);
        }
        .case-back:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        .case-title {
            font-family: var(--font-serif);
            font-size: 1.25rem;            /* 20px — clearly the page title */
            font-weight: 500;
            color: var(--fg);
            line-height: 1.2;
            letter-spacing: -0.008em;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;                   /* allow ellipsis under flex */
            flex-shrink: 1;
        }

        .case-meta {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.625rem;
            margin: 0;
            font-size: 0.8125rem;          /* 13px (was 11) */
            color: var(--fg-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
            margin-left: auto;             /* push meta to the right of title */
            flex-shrink: 0;
        }
        .case-meta-sep {
            color: color-mix(in srgb, var(--border) 60%, transparent);
            user-select: none;
            font-weight: 400;
        }
        .case-meta .font-mono {
            font-size: 0.8125rem;          /* 13px (was 11) */
            background: color-mix(in srgb, var(--fg) 5%, transparent);
            padding: 0.125rem 0.4375rem;
            border-radius: 4px;
            color: var(--fg);
            text-transform: none;
            letter-spacing: 0;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .case-meta { display: none; }   /* hide on narrow screens — title + status only */
            .case-status-badge { margin-left: auto; }
        }
        .case-meta-sep {
            color: color-mix(in srgb, var(--border) 60%, transparent);
            user-select: none;
        }
        .case-meta .font-mono {
            font-size: 0.78125rem;
            background: color-mix(in srgb, var(--fg) 5%, transparent);
            padding: 0.0625rem 0.4rem;
            border-radius: 4px;
            color: var(--fg);
        }
        .case-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;            /* 12px (was 10) */
            padding: 0.1875rem 0.625rem;
            border-radius: 999px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .case-status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }
        .case-status-active {
            background: color-mix(in srgb, var(--success) 14%, transparent);
            color: var(--success);
        }
        .case-status-closed {
            background: color-mix(in srgb, var(--fg-muted) 12%, transparent);
            color: var(--fg-muted);
        }

        /* ── Decorated tab strip — chamber-grade ──────────────────────────
           Each tab is a soft pill on hover; active tab gets a filled
           accent-tinted card with a gold dot. The strip itself sits on a
           subtle gradient under-rail that fades to transparent at edges. */
        .case-tabs {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.375rem;                /* tighter — pills give visual breathing room */
            margin-top: 0.625rem;
            padding: 0.25rem 0.25rem 0.375rem;
            overflow-x: auto;
            scrollbar-width: none;
            scroll-snap-type: x mandatory;
        }
        .case-tabs::-webkit-scrollbar { display: none; }
        /* Gradient under-rail — fades at edges */
        .case-tabs::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent 0%,
                color-mix(in srgb, var(--accent) 25%, var(--border)) 12%,
                color-mix(in srgb, var(--accent) 35%, var(--border)) 50%,
                color-mix(in srgb, var(--accent) 25%, var(--border)) 88%,
                transparent 100%
            );
            pointer-events: none;
        }

        .case-tab {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;        /* pill padding (was 0.75 0.125) */
            font-family: var(--font-sans);
            font-size: 1rem;                  /* 16px — slightly down from 17 since pill needs less weight */
            font-weight: 500;
            line-height: 1;
            color: color-mix(in srgb, var(--fg) 62%, transparent);
            text-decoration: none;
            white-space: nowrap;
            scroll-snap-align: start;
            border-radius: 9px;
            border: 1px solid transparent;
            transition: color 160ms ease-out, background 160ms ease-out,
                        border-color 160ms ease-out, transform 120ms ease-out,
                        box-shadow 220ms ease-out;
        }
        .case-tab:hover {
            color: var(--fg);
            background: color-mix(in srgb, var(--accent) 6%, transparent);
            border-color: color-mix(in srgb, var(--accent) 16%, var(--border));
        }
        .case-tab:active {
            transform: translateY(0.5px);
        }
        .case-tab:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
        .case-tab.is-active {
            color: var(--fg);
            font-weight: 600;
            letter-spacing: -0.005em;
            background: linear-gradient(
                180deg,
                color-mix(in srgb, var(--accent) 14%, var(--surface)) 0%,
                color-mix(in srgb, var(--accent) 8%, var(--surface)) 100%
            );
            border-color: color-mix(in srgb, var(--accent) 35%, var(--border));
            box-shadow:
                0 1px 2px color-mix(in srgb, var(--accent) 18%, transparent),
                inset 0 1px 0 color-mix(in srgb, #fff 14%, transparent);
        }
        /* Gold dot indicator on active tab */
        .case-tab.is-active::before {
            content: '';
            display: inline-block;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 6px color-mix(in srgb, var(--accent) 60%, transparent);
            margin-right: -0.125rem;        /* nudge into existing gap */
            flex-shrink: 0;
        }

        .case-tab-label {
            display: inline-block;
        }

        .case-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.4375rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            font-variant-numeric: tabular-nums;
            background: color-mix(in srgb, var(--fg) 6%, transparent);
            color: color-mix(in srgb, var(--fg) 70%, transparent);
            border-radius: 999px;
            transition: background 160ms ease-out, color 160ms ease-out;
        }
        .case-tab.is-active .case-tab-count {
            background: var(--accent);
            color: var(--primary-fg, #fff);
            box-shadow: 0 1px 3px color-mix(in srgb, var(--accent) 35%, transparent);
        }

        @media (max-width: 768px) {
            .case-tabs { gap: 0.25rem; padding-left: 0; padding-right: 0; }
            .case-tab { padding: 0.5rem 0.625rem; font-size: 0.9375rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .case-tab { transition: none; }
        }

        /* ── Content ── */
        .case-tab-content {
            background: var(--bg);
            min-height: 60vh;
            animation: case-tab-fade 240ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        .case-tab-inner {
            padding-top: 1.25rem;            /* was var(--case-pad-y-lg) = 3rem */
            padding-bottom: 1.5rem;          /* was 3rem */
            max-width: 1100px;
        }

        @keyframes case-tab-fade {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .case-tab-content { animation: none; }
            .case-tab, .case-card { transition: none; }
        }

        /* ── Premium card surface ── */
        .case-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.75rem;
            transition: border-color 220ms ease-out, box-shadow 220ms ease-out, transform 220ms ease-out;
        }
        .case-card-hoverable {
            cursor: pointer;
        }
        .case-card-hoverable:hover {
            border-color: color-mix(in srgb, var(--accent) 60%, var(--border));
            box-shadow: 0 6px 20px -10px color-mix(in srgb, var(--primary) 25%, transparent);
            transform: translateY(-1px);
        }
        .case-card-hoverable:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
        .case-card + .case-card {
            margin-top: 0.875rem;
        }
        .case-section-title {
            font-family: var(--font-serif);
            font-size: 1.3125rem;
            font-weight: 500;
            color: var(--fg);
            margin: 0 0 0.5rem;
            letter-spacing: -0.01em;
        }
        .case-section-sub {
            font-size: 0.875rem;
            color: var(--fg-muted);
            margin: 0 0 1.5rem;
            line-height: 1.55;
        }

        /* Premium primary button */
        .case-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6875rem 1.25rem;
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            font-weight: 500;
            background: var(--primary);
            color: var(--primary-fg);
            border: 1px solid var(--primary);
            border-radius: 9px;
            text-decoration: none;
            transition: transform 120ms ease-out, box-shadow 220ms ease-out, background 180ms ease-out;
            cursor: pointer;
            letter-spacing: 0.005em;
        }
        .case-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--primary) 35%, transparent),
                        0 2px 4px -2px color-mix(in srgb, var(--primary) 30%, transparent);
        }
        .case-btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px color-mix(in srgb, var(--primary) 20%, transparent);
        }
        .case-btn-primary:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }
        .case-btn-primary[disabled], .case-btn-primary.is-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .case-btn-primary[disabled]:hover, .case-btn-primary.is-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .case-btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6875rem 1.25rem;
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            font-weight: 500;
            background: transparent;
            color: var(--fg);
            border: 1px solid var(--border);
            border-radius: 9px;
            text-decoration: none;
            transition: background 150ms ease-out, border-color 150ms ease-out;
            cursor: pointer;
        }
        .case-btn-ghost:hover {
            background: color-mix(in srgb, var(--fg) 4%, transparent);
            border-color: color-mix(in srgb, var(--fg) 20%, var(--border));
        }
        .case-btn-ghost:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }
    </style>
</x-layouts.app-shell>
