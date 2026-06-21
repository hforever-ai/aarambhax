@php
    // Revision indicator
    if ($note->ai_status === 'done') {
        $days = $note->updated_at->diffInDays(now());
        if ($days < 3)      { $revStripe = 'sni-rev-fresh';  $revLabel = 'Fresh';        $revBadge = 'sni-mini-badge-green'; }
        elseif ($days < 7)  { $revStripe = 'sni-rev-soon';   $revLabel = 'Review Soon';  $revBadge = 'sni-mini-badge-warn'; }
        else                { $revStripe = 'sni-rev-due';    $revLabel = 'Revise Now';   $revBadge = 'sni-mini-badge-danger'; }
    } else {
        $revStripe = 'sni-rev-none'; $revLabel = null; $revBadge = null;
    }
    $isPdf = $note->image_path && str_ends_with(strtolower($note->image_path), '.pdf');
@endphp

<a href="{{ route('app.student-notes.show', $note) }}" class="sni-card">
    {{-- Revision stripe --}}
    <div class="sni-rev-stripe {{ $revStripe }}"></div>

    {{-- Thumbnail --}}
    <div class="sni-card-img-wrap">
        @if($note->image_path)
            @if($isPdf)
                <div class="sni-card-img-placeholder sni-card-pdf-thumb">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
                    <span>PDF</span>
                </div>
            @else
                <img src="{{ asset('storage/'.$note->image_path) }}" alt="" class="sni-card-img" loading="lazy">
            @endif
        @else
            <div class="sni-card-img-placeholder">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
        @endif

        {{-- Scan status badge --}}
        @if($note->ai_status === 'done')
            <span class="sni-status-badge sni-badge-done">✓ Scanned</span>
        @elseif($note->ai_status === 'scanning')
            <span class="sni-status-badge sni-badge-scanning">Scanning…</span>
        @elseif($note->ai_status === 'failed')
            <span class="sni-status-badge sni-badge-fail">Failed</span>
        @endif
    </div>

    <div class="sni-card-body">
        {{-- Subject + Class tags --}}
        @if($note->subject || $note->class_name)
            <div class="sni-card-tags">
                @if($note->subject)    <span class="sni-tag">{{ $note->subject }}</span> @endif
                @if($note->class_name) <span class="sni-tag sni-tag-green">{{ $note->class_name }}</span> @endif
            </div>
        @endif

        <h3 class="sni-card-title">{{ $note->title ?: Str::limit($note->ocr_text ?: $note->note_text ?: 'Untitled', 45) }}</h3>

        @if($note->organised_md)
            <p class="sni-card-snippet">{{ Str::limit(strip_tags($note->organised_md), 80) }}</p>
        @elseif($note->note_text)
            <p class="sni-card-snippet">{{ Str::limit($note->note_text, 80) }}</p>
        @endif

        <div class="sni-card-foot">
            <span class="sni-card-date">{{ $note->created_at->format('d M') }}</span>
            @if($revLabel)
                <span class="sni-mini-badge {{ $revBadge }}">{{ $revLabel }}</span>
            @endif
            @if($note->questions_json)
                <span class="sni-mini-badge">Q&amp;A</span>
            @endif
        </div>
    </div>
</a>
