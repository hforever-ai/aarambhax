<x-layouts.student-shell title="My Notes — Zenith">
<section class="sni-page">
<div class="container-page sni-inner">

    {{-- Header --}}
    <div class="sni-header">
        <div>
            <h1 class="sni-heading">My Notes</h1>
            <p class="sni-sub">{{ $notes->count() }} {{ Str::plural('note', $notes->count()) }}
                @if($filterSubject || $filterClass || $search) · filtered @endif
            </p>
        </div>
        <a href="{{ route('app.dashboard') }}" class="sni-btn-add">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Note
        </a>
    </div>

    @if(session('flash'))
        <div class="sni-flash">{{ session('flash') }}</div>
    @endif

    {{-- Toolbar: search + view mode --}}
    <div class="sni-toolbar">
        <div class="sni-toolbar-top">
            <form method="GET" action="{{ route('app.student-notes.index') }}" class="sni-search-form">
                <input type="hidden" name="view" value="{{ $viewMode }}">
                @if($filterSubject) <input type="hidden" name="subject" value="{{ $filterSubject }}"> @endif
                @if($filterClass)   <input type="hidden" name="class_name" value="{{ $filterClass }}"> @endif
                <input type="text" name="q" value="{{ $search }}" placeholder="Search notes…" class="sni-search">
                <button type="submit" class="sni-search-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </form>

            {{-- View mode tabs --}}
            <div class="sni-view-tabs">
                @foreach(['subject' => 'By Subject', 'month' => 'By Month', 'revision' => 'Revision'] as $mode => $label)
                    <a href="{{ route('app.student-notes.index', array_filter(['view' => $mode, 'q' => $search, 'subject' => $filterSubject, 'class_name' => $filterClass])) }}"
                       class="sni-view-tab {{ $viewMode === $mode ? 'is-active' : '' }}
                              {{ $mode === 'revision' ? 'sni-view-tab-revision' : '' }}">
                        @if($mode === 'revision')
                            <span class="sni-rev-dot"></span>
                        @endif
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Subject / class filter chips --}}
        <div class="sni-filters">
            <a href="{{ route('app.student-notes.index', array_filter(['view' => $viewMode, 'q' => $search])) }}"
               class="sni-chip {{ !$filterSubject && !$filterClass ? 'is-active' : '' }}">All</a>
            @foreach($allSubjects as $s)
                <a href="{{ route('app.student-notes.index', array_filter(['view' => $viewMode, 'subject' => $s, 'q' => $search])) }}"
                   class="sni-chip {{ $filterSubject === $s ? 'is-active' : '' }}">{{ $s }}</a>
            @endforeach
            @foreach($allClasses as $c)
                <a href="{{ route('app.student-notes.index', array_filter(['view' => $viewMode, 'class_name' => $c, 'q' => $search])) }}"
                   class="sni-chip sni-chip-green {{ $filterClass === $c ? 'is-active' : '' }}">{{ $c }}</a>
            @endforeach
        </div>
    </div>

    {{-- Empty state --}}
    @if($notes->isEmpty())
        <div class="sni-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            @if($viewMode === 'revision')
                <p>No notes ready for revision yet. Scan your notes first.</p>
            @else
                <p>No notes found.</p>
                <a href="{{ route('app.dashboard') }}" class="sni-btn-add">Upload your first note</a>
            @endif
        </div>

    @elseif($viewMode === 'subject')
        {{-- ── SUBJECT VIEW ── --}}
        @php
            $grouped = $notes->groupBy(fn($n) => $n->subject ?: 'Uncategorised');
            $grouped = $grouped->sortKeys();
            // Put Uncategorised last
            if ($grouped->has('Uncategorised')) {
                $unc = $grouped->pull('Uncategorised');
                $grouped->put('Uncategorised', $unc);
            }
        @endphp

        @foreach($grouped as $subject => $subjectNotes)
            <div class="sni-section">
                <div class="sni-section-header">
                    <h2 class="sni-section-title">{{ $subject }}</h2>
                    <span class="sni-section-count">{{ $subjectNotes->count() }} {{ Str::plural('note', $subjectNotes->count()) }}</span>
                </div>
                <div class="sni-grid">
                    @foreach($subjectNotes as $note)
                        @include('app.student_notes._card', compact('note'))
                    @endforeach
                </div>
            </div>
        @endforeach

    @elseif($viewMode === 'month')
        {{-- ── MONTH VIEW ── --}}
        @php
            $grouped = $notes->groupBy(fn($n) => $n->created_at->format('F Y'));
        @endphp

        @foreach($grouped as $month => $monthNotes)
            <div class="sni-section">
                <div class="sni-section-header">
                    <h2 class="sni-section-title">{{ $month }}</h2>
                    <span class="sni-section-count">{{ $monthNotes->count() }} {{ Str::plural('note', $monthNotes->count()) }}</span>
                </div>
                <div class="sni-grid">
                    @foreach($monthNotes as $note)
                        @include('app.student_notes._card', compact('note'))
                    @endforeach
                </div>
            </div>
        @endforeach

    @else
        {{-- ── REVISION VIEW ── oldest-scanned first --}}
        <div class="sni-revision-banner">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Sorted oldest-reviewed first — tackle the red ones first.
        </div>
        <div class="sni-grid">
            @foreach($notes as $note)
                @include('app.student_notes._card', compact('note'))
            @endforeach
        </div>
    @endif

</div>
</section>

<style>
    .sni-page { background: var(--bg); min-height: 100vh; }
    .sni-inner { padding-top: 2rem; padding-bottom: 4rem; max-width: 1200px; }

    /* Header */
    .sni-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .sni-heading { font-family: var(--font-serif); font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 500; color: var(--fg); margin: 0; line-height: 1.2; }
    .sni-sub { font-size: 0.875rem; color: var(--fg-muted); margin: 0.25rem 0 0; }
    .sni-btn-add { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.625rem 1.125rem; background: var(--primary); color: var(--primary-fg); border-radius: 9px; font-size: 0.875rem; font-weight: 600; text-decoration: none; transition: opacity 150ms; flex-shrink: 0; }
    .sni-btn-add:hover { opacity: 0.85; }

    .sni-flash { background: color-mix(in srgb, var(--success) 12%, var(--surface)); border: 1px solid color-mix(in srgb, var(--success) 35%, var(--border)); color: var(--success); border-radius: 8px; padding: 0.625rem 1rem; font-size: 0.875rem; margin-bottom: 1.25rem; }

    /* Toolbar */
    .sni-toolbar { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.75rem; }
    .sni-toolbar-top { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
    .sni-search-form { display: flex; max-width: 320px; flex: 1; }
    .sni-search { flex: 1; background: var(--surface); border: 1px solid var(--border); border-right: none; border-radius: 8px 0 0 8px; padding: 0.5rem 0.875rem; font-size: 0.875rem; color: var(--fg); outline: none; }
    .sni-search:focus { border-color: color-mix(in srgb, var(--accent) 60%, var(--border)); }
    .sni-search-btn { background: var(--surface); border: 1px solid var(--border); border-radius: 0 8px 8px 0; padding: 0 0.875rem; color: var(--fg-muted); cursor: pointer; }

    /* View mode tabs */
    .sni-view-tabs { display: flex; gap: 0.25rem; background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 0.25rem; }
    .sni-view-tab { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; font-size: 0.8125rem; font-weight: 500; color: var(--fg-muted); text-decoration: none; border-radius: 6px; transition: all 150ms; }
    .sni-view-tab:hover { color: var(--fg); background: color-mix(in srgb, var(--fg) 5%, transparent); }
    .sni-view-tab.is-active { color: var(--fg); background: var(--bg); box-shadow: 0 1px 4px -1px rgba(0,0,0,0.12); font-weight: 600; }
    .sni-view-tab-revision.is-active { color: var(--danger); }
    .sni-rev-dot { width: 7px; height: 7px; background: var(--danger); border-radius: 50%; flex-shrink: 0; }

    /* Filter chips */
    .sni-filters { display: flex; flex-wrap: wrap; gap: 0.375rem; }
    .sni-chip { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border: 1px solid var(--border); border-radius: 99px; font-size: 0.75rem; font-weight: 500; color: var(--fg-muted); text-decoration: none; background: var(--surface); transition: border-color 150ms, color 150ms; }
    .sni-chip:hover { color: var(--fg); border-color: color-mix(in srgb, var(--accent) 40%, var(--border)); }
    .sni-chip.is-active { background: color-mix(in srgb, var(--accent) 14%, var(--surface)); border-color: color-mix(in srgb, var(--accent) 50%, var(--border)); color: var(--accent); font-weight: 600; }
    .sni-chip-green.is-active { background: color-mix(in srgb, var(--success) 12%, var(--surface)); border-color: color-mix(in srgb, var(--success) 40%, var(--border)); color: var(--success); }

    /* Section grouping */
    .sni-section { margin-bottom: 2.5rem; }
    .sni-section-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.875rem; padding-bottom: 0.625rem; border-bottom: 1px solid var(--border); }
    .sni-section-title { font-size: 0.9375rem; font-weight: 700; color: var(--fg); margin: 0; }
    .sni-section-count { font-size: 0.75rem; color: var(--fg-muted); background: color-mix(in srgb, var(--fg) 6%, var(--surface)); border: 1px solid var(--border); border-radius: 99px; padding: 0.125rem 0.5rem; }

    /* Revision banner */
    .sni-revision-banner { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: var(--fg-muted); background: color-mix(in srgb, var(--danger) 8%, var(--surface)); border: 1px solid color-mix(in srgb, var(--danger) 25%, var(--border)); border-radius: 8px; padding: 0.625rem 0.875rem; margin-bottom: 1.25rem; }

    /* Empty */
    .sni-empty { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 5rem 2rem; color: var(--fg-muted); text-align: center; }

    /* Grid */
    .sni-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 1rem; }

    /* Card */
    .sni-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; text-decoration: none; color: inherit; transition: transform 200ms ease-out, box-shadow 200ms ease-out, border-color 150ms; }
    .sni-card:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--accent) 35%, var(--border)); box-shadow: 0 8px 24px -10px color-mix(in srgb, var(--accent) 18%, transparent); }

    /* Card image */
    .sni-card-img-wrap { position: relative; aspect-ratio: 4/3; overflow: hidden; background: var(--bg); }
    .sni-card-img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 300ms ease-out; }
    .sni-card:hover .sni-card-img { transform: scale(1.04); }
    .sni-card-img-placeholder { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.25rem; color: var(--fg-muted); background: color-mix(in srgb, var(--accent) 4%, var(--surface)); }
    .sni-card-pdf-thumb { background: color-mix(in srgb, var(--accent) 7%, var(--surface)); color: var(--accent); font-size: 0.625rem; font-weight: 700; letter-spacing: .08em; }

    /* Status badge on image */
    .sni-status-badge { position: absolute; bottom: 0.5rem; right: 0.5rem; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 0.1875rem 0.5rem; border-radius: 99px; }
    .sni-badge-done    { background: color-mix(in srgb, var(--success) 85%, transparent); color: #fff; }
    .sni-badge-scanning { background: color-mix(in srgb, var(--accent) 80%, transparent); color: #fff; }
    .sni-badge-fail    { background: color-mix(in srgb, var(--danger) 80%, transparent); color: #fff; }

    /* Revision indicator stripe at top of card */
    .sni-rev-stripe { height: 3px; width: 100%; flex-shrink: 0; }
    .sni-rev-fresh  { background: var(--success); }
    .sni-rev-soon   { background: #f59e0b; }
    .sni-rev-due    { background: var(--danger); }
    .sni-rev-none   { background: var(--border); }

    /* Card body */
    .sni-card-body { padding: 0.875rem; display: flex; flex-direction: column; gap: 0.375rem; flex: 1; }
    .sni-card-tags { display: flex; flex-wrap: wrap; gap: 0.25rem; }
    .sni-tag { font-size: 0.575rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 0.125rem 0.4375rem; border-radius: 99px; background: color-mix(in srgb, var(--accent) 12%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 28%, var(--border)); color: var(--accent); }
    .sni-tag-green { background: color-mix(in srgb, var(--success) 10%, transparent); border-color: color-mix(in srgb, var(--success) 28%, var(--border)); color: var(--success); }
    .sni-card-title { font-size: 0.9375rem; font-weight: 600; color: var(--fg); margin: 0; line-height: 1.35; }
    .sni-card-snippet { font-size: 0.8rem; color: var(--fg-muted); margin: 0; line-height: 1.5; }
    .sni-card-foot { display: flex; align-items: center; gap: 0.375rem; margin-top: auto; padding-top: 0.5rem; flex-wrap: wrap; }
    .sni-card-date { font-size: 0.6875rem; color: var(--fg-muted); flex: 1; }
    .sni-mini-badge { font-size: 0.575rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.125rem 0.4rem; border-radius: 4px; background: color-mix(in srgb, var(--accent) 14%, var(--surface)); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 28%, var(--border)); }
    .sni-mini-badge-green { background: color-mix(in srgb, var(--success) 12%, var(--surface)); color: var(--success); border-color: color-mix(in srgb, var(--success) 28%, var(--border)); }
    .sni-mini-badge-warn  { background: color-mix(in srgb, #f59e0b 12%, var(--surface)); color: #b45309; border-color: color-mix(in srgb, #f59e0b 35%, var(--border)); }
    .sni-mini-badge-danger { background: color-mix(in srgb, var(--danger) 12%, var(--surface)); color: var(--danger); border-color: color-mix(in srgb, var(--danger) 28%, var(--border)); }
</style>
</x-layouts.student-shell>
