<x-layouts.student-shell title="{{ $note->title ?? 'Note' }} — Zenith">
<div class="z-page">
<div class="z-container">

    {{-- Header --}}
    <div class="z-header">
        <a href="{{ route('app.student-notes.index') }}" class="z-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            My Notes
        </a>
        <div class="z-title-row">
            <div class="z-title-wrap">
                @php
                    $days = $note->ai_status === 'done' ? $note->updated_at->diffInDays(now()) : null;
                    $stripe = $days === null ? '' : ($days < 3 ? '#22c55e' : ($days < 7 ? '#f59e0b' : '#ef4444'));
                    $revLabel = $days === null ? null : ($days < 3 ? 'Fresh' : ($days < 7 ? 'Review Soon' : 'Revise Now'));
                @endphp
                @if($stripe)
                    <span class="z-rev-dot" style="background:{{ $stripe }}"></span>
                @endif
                <h1 class="z-title">{{ $note->title ?: 'Untitled Note' }}</h1>
            </div>
            <div class="z-meta-pills">
                @if($note->subject)    <span class="z-pill z-pill-accent">{{ $note->subject }}</span> @endif
                @if($note->class_name) <span class="z-pill z-pill-blue">{{ $note->class_name }}</span> @endif
                @if($revLabel)         <span class="z-pill" style="border-color:{{ $stripe }};color:{{ $stripe }}">{{ $revLabel }}</span> @endif
                <span class="z-pill z-pill-muted">{{ $note->created_at->format('d M Y') }}</span>
            </div>
        </div>
    </div>

    {{-- 4 Main Tabs --}}
    @php
        $activeTab = request('tab', 'read');
        $tabs = [
            'read'     => 'Read',
            'practice' => 'Practice',
            'insights' => 'Insights',
            'ask'      => 'Ask AI',
        ];
    @endphp
    <div class="z-tabs">
        @foreach($tabs as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
               class="z-tab {{ $activeTab === $key ? 'is-active' : '' }}
                      {{ $key === 'ask' ? 'z-tab-ai' : '' }}">
                @if($key === 'ask')
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                @endif
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Tab content --}}
    <div class="z-body">

        {{-- ── READ TAB ── --}}
        @if($activeTab === 'read')
        <div class="z-read-layout">

            {{-- Sidebar: photo + actions --}}
            <aside class="z-sidebar">
                @if($note->image_path)
                    @php $isPdf = str_ends_with(strtolower($note->image_path), '.pdf'); @endphp
                    @if($isPdf)
                        <div class="z-pdf-box">
                            <div class="z-pdf-header">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                PDF
                                <a href="{{ asset('storage/'.$note->image_path) }}" target="_blank" class="z-pdf-open">Open ↗</a>
                            </div>
                            <iframe src="{{ asset('storage/'.$note->image_path) }}#toolbar=0" class="z-pdf-iframe" title="PDF"></iframe>
                        </div>
                    @else
                        <a href="{{ asset('storage/'.$note->image_path) }}" target="_blank">
                            <img src="{{ asset('storage/'.$note->image_path) }}" alt="Note scan" class="z-scan-img">
                        </a>
                    @endif
                @endif

                {{-- Action buttons --}}
                <div class="z-actions">
                    <form method="POST" action="{{ route('app.student-notes.scan', $note) }}">
                        @csrf
                        <button class="z-btn z-btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                            {{ $note->ai_status === 'done' ? 'Re-scan' : 'Scan & Organise' }}
                        </button>
                    </form>
                    @if($note->ai_status === 'done')
                    <form method="POST" action="{{ route('app.student-notes.questions', $note) }}">
                        @csrf <button class="z-btn z-btn-ghost">Generate Questions</button>
                    </form>
                    <form method="POST" action="{{ route('app.student-notes.formula-sheet', $note) }}">
                        @csrf <button class="z-btn z-btn-ghost">Formula Sheet</button>
                    </form>
                    <form method="POST" action="{{ route('app.student-notes.flashcards', $note) }}">
                        @csrf <button class="z-btn z-btn-ghost">Flashcards</button>
                    </form>
                    <form method="POST" action="{{ route('app.student-notes.polish', $note) }}">
                        @csrf <button class="z-btn z-btn-ghost">Polish Notes</button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('app.student-notes.destroy', $note) }}" onsubmit="return confirm('Delete this note?')">
                        @csrf @method('DELETE')
                        <button class="z-btn z-btn-danger">Delete</button>
                    </form>
                </div>

                @if($note->note_text)
                    <div class="z-original-note">
                        <div class="z-label">Your note</div>
                        <p class="z-original-text">{{ $note->note_text }}</p>
                    </div>
                @endif
            </aside>

            {{-- Main content --}}
            <div class="z-content">
                @if($note->ai_status === 'none')
                    <div class="z-empty">
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                        <p>Hit <strong>Scan & Organise</strong> to extract and structure your notes with AI.</p>
                    </div>
                @elseif($note->ai_status === 'scanning')
                    <div class="z-empty">
                        <div class="z-spinner"></div>
                        <p>Scanning your notes…</p>
                    </div>
                @elseif($note->ai_status === 'failed')
                    <div class="z-empty z-empty-fail"><p>Scan failed — try again.</p></div>
                @else
                    {{-- Sub-toggle: Organised / Polished / Raw --}}
                    @php $subView = request('view', 'organised'); @endphp
                    <div class="z-sub-tabs">
                        @if($note->organised_md)
                            <a href="{{ request()->fullUrlWithQuery(['tab'=>'read','view'=>'organised']) }}" class="z-sub-tab {{ $subView==='organised'?'is-active':'' }}">Organised</a>
                        @endif
                        @if($note->polished_md)
                            <a href="{{ request()->fullUrlWithQuery(['tab'=>'read','view'=>'polished']) }}" class="z-sub-tab {{ $subView==='polished'?'is-active':'' }}">Polished</a>
                        @endif
                        @if($note->ocr_text)
                            <a href="{{ request()->fullUrlWithQuery(['tab'=>'read','view'=>'raw']) }}" class="z-sub-tab {{ $subView==='raw'?'is-active':'' }}">Raw OCR</a>
                        @endif
                    </div>

                    @if($subView === 'raw' && $note->ocr_text)
                        <pre class="z-raw">{{ $note->ocr_text }}</pre>
                    @elseif($subView === 'polished' && $note->polished_md)
                        <div class="z-prose">{!! \Illuminate\Support\Str::of($note->polished_md)->markdown() !!}</div>
                    @elseif($note->organised_md)
                        <div class="z-prose">{!! \Illuminate\Support\Str::of($note->organised_md)->markdown() !!}</div>
                    @endif
                @endif
            </div>
        </div>

        {{-- ── PRACTICE TAB ── --}}
        @elseif($activeTab === 'practice')
        <div class="z-practice-wrap">
            <div class="z-tab-header">
                <div>
                    <h2 class="z-tab-title">Practice Questions</h2>
                    <p class="z-tab-sub">JEE-style questions from your notes</p>
                </div>
                @if($note->ai_status === 'done')
                <form method="POST" action="{{ route('app.student-notes.questions', $note) }}">
                    @csrf <button class="z-btn z-btn-primary">{{ $note->questions_json ? 'Regenerate' : 'Generate Questions' }}</button>
                </form>
                @endif
            </div>

            @if(!$note->questions_json)
                <div class="z-empty">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <p>{{ $note->ai_status !== 'done' ? 'Scan your notes first, then generate questions.' : 'Tap Generate Questions above.' }}</p>
                </div>
            @else
                @php $questions = json_decode($note->questions_json, true) ?? []; @endphp
                <div class="z-questions">
                    @foreach($questions as $i => $q)
                    <div class="z-q-card">
                        <div class="z-q-meta">
                            <span class="z-q-num">Q{{ $i+1 }}</span>
                            <span class="z-q-type">{{ strtoupper($q['type'] ?? 'SHORT') }}</span>
                        </div>
                        <p class="z-q-text">{{ $q['question'] }}</p>
                        @if(!empty($q['options']))
                        <ul class="z-q-opts">
                            @foreach($q['options'] as $opt)
                            <li class="{{ str_starts_with($opt, ($q['answer']??'').'.') ? 'z-correct' : '' }}">{{ $opt }}</li>
                            @endforeach
                        </ul>
                        @endif
                        <details class="z-answer">
                            <summary>Show Answer</summary>
                            <p>{{ $q['answer'] }}</p>
                        </details>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

            {{-- ── JEE PYQs Section ── --}}
            @if(count($matchedPYQs) > 0)
            <div class="z-pyq-wrap">
                <div class="z-pyq-header">
                    <div>
                        <h3 class="z-pyq-title">JEE PYQs — Matched to Your Notes</h3>
                        <p class="z-pyq-sub">
                            Topics: {{ implode(' · ', $note->matched_topics ?? []) }}
                        </p>
                    </div>
                    <span class="z-pyq-badge">{{ count($matchedPYQs) }} questions</span>
                </div>

                <div class="z-pyq-list" id="pyq-list">
                @foreach($matchedPYQs as $pyq)
                    @php
                        $attempted = $pyq->attempt_status;
                        $cardClass = $attempted === 'understood' ? 'z-pyq-card z-pyq-got'
                                   : ($attempted === 'not_understood' ? 'z-pyq-card z-pyq-tricky'
                                   : 'z-pyq-card');
                    @endphp
                    <div class="{{ $cardClass }}" data-pyq="{{ $pyq->id }}">
                        <div class="z-pyq-meta">
                            <span class="z-pyq-year">JEE {{ $pyq->year }}</span>
                            <span class="z-pyq-topic">{{ $pyq->topic }}</span>
                            <span class="z-pyq-type">{{ strtoupper($pyq->type) }}</span>
                            @if($attempted === 'understood')
                                <span class="z-pyq-status z-pyq-status-got">✓ Got it</span>
                            @elseif($attempted === 'not_understood')
                                <span class="z-pyq-status z-pyq-status-tricky">✗ Tricky</span>
                            @endif
                        </div>
                        <p class="z-pyq-q">{{ $pyq->question }}</p>

                        @if($pyq->options)
                        <ul class="z-pyq-opts">
                            @foreach($pyq->options as $opt)
                                <li>{{ $opt }}</li>
                            @endforeach
                        </ul>
                        @endif

                        <details class="z-pyq-details">
                            <summary>Show Answer &amp; Solution</summary>
                            <div class="z-pyq-solution">
                                <strong>Answer: {{ $pyq->answer }}</strong>
                                @if($pyq->solution)
                                    <p>{{ $pyq->solution }}</p>
                                @endif
                            </div>
                        </details>

                        <div class="z-pyq-actions">
                            <button class="z-pyq-btn z-pyq-btn-got"
                                    onclick="markPYQ({{ $note->id }}, {{ $pyq->id }}, 'understood', this)">
                                ✓ Got it
                            </button>
                            <button class="z-pyq-btn z-pyq-btn-tricky"
                                    onclick="markPYQ({{ $note->id }}, {{ $pyq->id }}, 'not_understood', this)">
                                ✗ Tricky
                            </button>
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
            @elseif($note->ai_status === 'done' && empty($note->matched_topics))
            <div class="z-pyq-empty">
                <p>No JEE PYQs matched yet.</p>
                <p style="font-size:0.8rem;color:var(--fg-subtle);margin-top:0.25rem">Re-scan the note to auto-match topics, or PYQ bank may not cover this topic yet.</p>
            </div>
            @endif

        {{-- ── INSIGHTS TAB ── --}}
        @elseif($activeTab === 'insights')
        <div class="z-insights-wrap">

            {{-- Formula Sheet --}}
            <div class="z-insight-block">
                <div class="z-insight-header">
                    <div class="z-insight-icon z-icon-saffron">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
                    </div>
                    <div>
                        <h3 class="z-insight-title">Formula Sheet</h3>
                        <p class="z-insight-sub">All equations extracted from your notes</p>
                    </div>
                    @if($note->ai_status === 'done')
                    <form method="POST" action="{{ route('app.student-notes.formula-sheet', $note) }}" class="ml-auto">
                        @csrf <button class="z-btn z-btn-sm">{{ $note->formula_sheet_md ? 'Refresh' : 'Generate' }}</button>
                    </form>
                    @endif
                </div>
                @if($note->formula_sheet_md)
                    <div class="z-prose z-formula-prose">{!! \Illuminate\Support\Str::of($note->formula_sheet_md)->markdown() !!}</div>
                @else
                    <p class="z-insight-empty">{{ $note->ai_status === 'done' ? 'Click Generate to extract all formulas.' : 'Scan your notes first.' }}</p>
                @endif
            </div>

            {{-- Flashcards --}}
            <div class="z-insight-block">
                <div class="z-insight-header">
                    <div class="z-insight-icon z-icon-blue">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    </div>
                    <div>
                        <h3 class="z-insight-title">Flashcards</h3>
                        <p class="z-insight-sub">Active recall — tap to flip</p>
                    </div>
                    @if($note->ai_status === 'done')
                    <form method="POST" action="{{ route('app.student-notes.flashcards', $note) }}" class="ml-auto">
                        @csrf <button class="z-btn z-btn-sm">{{ $note->flashcards_json ? 'Refresh' : 'Generate' }}</button>
                    </form>
                    @endif
                </div>
                @if($note->flashcards_json)
                    @php $cards = json_decode($note->flashcards_json, true) ?? []; @endphp
                    <div class="z-flashcards">
                        @foreach($cards as $i => $card)
                        <div class="z-flashcard" onclick="this.classList.toggle('is-flipped')" title="Tap to flip">
                            <div class="z-fc-inner">
                                <div class="z-fc-front">
                                    <span class="z-fc-type">{{ $card['type'] ?? 'card' }}</span>
                                    <p>{{ $card['front'] }}</p>
                                    <span class="z-fc-hint">tap to reveal</span>
                                </div>
                                <div class="z-fc-back">
                                    <p>{{ $card['back'] }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="z-insight-empty">{{ $note->ai_status === 'done' ? 'Click Generate to create flashcards.' : 'Scan your notes first.' }}</p>
                @endif
            </div>

            {{-- Polished / Key Takeaways --}}
            @if($note->polished_md)
            <div class="z-insight-block">
                <div class="z-insight-header">
                    <div class="z-insight-icon z-icon-green">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div>
                        <h3 class="z-insight-title">Key Takeaways & JEE Pitfalls</h3>
                        <p class="z-insight-sub">AI-polished notes with common mistakes</p>
                    </div>
                </div>
                <div class="z-prose">{!! \Illuminate\Support\Str::of($note->polished_md)->markdown() !!}</div>
            </div>
            @endif
        </div>

        {{-- ── ASK AI TAB ── --}}
        @elseif($activeTab === 'ask')
        <div class="z-ask-wrap">
            <div class="z-ask-context">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                AI is answering using <strong>your notes</strong> on {{ $note->title ?: 'this topic' }} as context.
            </div>

            <div class="z-chat" id="z-chat">
                <div class="z-bubble z-bubble-ai">
                    <div class="z-bubble-text">Namaste! Ask me anything about <strong>{{ $note->title ?: 'these notes' }}</strong>. I'll answer using what you've written.</div>
                </div>
            </div>

            @if($note->ai_status !== 'done')
                <div class="z-ask-disabled">Scan your notes first to enable Ask AI.</div>
            @else
                <form class="z-ask-form" id="z-ask-form">
                    @csrf
                    <input type="text" id="z-question" placeholder="e.g. Explain Newton's 3rd law from my notes…" class="z-ask-input" maxlength="500" autocomplete="off">
                    <button type="submit" class="z-ask-btn" id="z-ask-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </form>
            @endif
        </div>
        @endif

    </div>{{-- /z-body --}}

</div>
</div>

{{-- KaTeX --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css" crossorigin="anonymous">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js" crossorigin="anonymous"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js" crossorigin="anonymous"
    onload="renderMathInElement(document.querySelector('.z-page'),{delimiters:[{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}],throwOnError:false})"></script>

{{-- Ask AI AJAX --}}
@if(request('tab','read') === 'ask' && $note->ai_status === 'done')
<script>
document.getElementById('z-ask-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const q = document.getElementById('z-question').value.trim();
    if (!q) return;
    const chat = document.getElementById('z-chat');
    const btn  = document.getElementById('z-ask-btn');

    // User bubble
    chat.insertAdjacentHTML('beforeend', `<div class="z-bubble z-bubble-user"><div class="z-bubble-text">${q}</div></div>`);
    document.getElementById('z-question').value = '';
    btn.disabled = true;
    chat.insertAdjacentHTML('beforeend', '<div class="z-bubble z-bubble-ai" id="z-thinking"><div class="z-bubble-text z-typing"><span></span><span></span><span></span></div></div>');
    chat.scrollTop = chat.scrollHeight;

    try {
        const res = await fetch('{{ route('app.student-notes.ask', $note) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ question: q })
        });
        const data = await res.json();
        document.getElementById('z-thinking')?.remove();
        const text = data.answer ?? data.error ?? 'Something went wrong.';
        chat.insertAdjacentHTML('beforeend', `<div class="z-bubble z-bubble-ai"><div class="z-bubble-text">${text.replace(/\n/g,'<br>')}</div></div>`);
    } catch(err) {
        document.getElementById('z-thinking')?.remove();
        chat.insertAdjacentHTML('beforeend', `<div class="z-bubble z-bubble-ai"><div class="z-bubble-text" style="color:var(--danger)">Error — try again.</div></div>`);
    }
    btn.disabled = false;
    chat.scrollTop = chat.scrollHeight;
});
</script>
@endif

<script>
function markPYQ(noteId, pyqId, status, clickedBtn) {
    const card = clickedBtn.closest('[data-pyq]');
    const buttons = card.querySelectorAll('.z-pyq-btn');
    buttons.forEach(b => b.classList.remove('is-active-got', 'is-active-tricky'));
    fetch(`/app/student-notes/${noteId}/pyqs/${pyqId}/attempt`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
        body: JSON.stringify({status})
    }).then(r => r.json()).then(data => {
        if (data.status === 'understood') {
            clickedBtn.classList.add('is-active-got');
            card.classList.replace('z-pyq-tricky', 'z-pyq-got') || card.classList.add('z-pyq-got');
        } else {
            clickedBtn.classList.add('is-active-tricky');
            card.classList.replace('z-pyq-got', 'z-pyq-tricky') || card.classList.add('z-pyq-tricky');
        }
    }).catch(() => {});
}
</script>

<style>
.z-page { min-height: 100vh; padding-top: 1.5rem; }
.z-container { max-width: 1100px; margin: 0 auto; padding: 0 1.25rem 3rem; }

/* Header */
.z-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--fg-muted); font-size: 0.8125rem; text-decoration: none; margin-bottom: 1rem; }
.z-back:hover { color: var(--fg); }
.z-header { margin-bottom: 1.25rem; }
.z-title-row { display: flex; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
.z-title-wrap { display: flex; align-items: center; gap: 0.625rem; flex: 1 1 60%; }
.z-rev-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 8px currentColor; }
.z-title { font-size: clamp(1.375rem,3vw,1.875rem); font-weight: 800; color: var(--fg); margin: 0; line-height: 1.2; }
.z-meta-pills { display: flex; flex-wrap: wrap; gap: 0.375rem; align-items: center; }
.z-pill { font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; padding: 0.2rem 0.5rem; border-radius: 99px; border: 1px solid var(--border); color: var(--fg-muted); }
.z-pill-accent { background: var(--accent-glow); border-color: rgba(245,158,11,0.4); color: var(--accent); }
.z-pill-blue   { background: rgba(37,99,235,0.12); border-color: rgba(96,165,250,0.35); color: #60a5fa; }
.z-pill-muted  { color: var(--fg-subtle); }

/* Tabs */
.z-tabs { display: flex; gap: 0.25rem; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem; overflow-x: auto; }
.z-tab { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 600; color: var(--fg-muted); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; transition: color 150ms; }
.z-tab:hover { color: var(--fg); }
.z-tab.is-active { color: var(--accent); border-bottom-color: var(--accent); }
.z-tab-ai.is-active { color: #60a5fa; border-bottom-color: #60a5fa; }

/* Read layout */
.z-read-layout { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media(min-width:768px) { .z-read-layout { grid-template-columns: 240px 1fr; } }

/* Sidebar */
.z-sidebar { display: flex; flex-direction: column; gap: 0.875rem; }
.z-scan-img { width: 100%; border-radius: 12px; border: 1px solid var(--border); display: block; max-height: 320px; object-fit: cover; }
.z-pdf-box { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.z-pdf-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface-2); font-size: 0.75rem; font-weight: 700; color: var(--fg-muted); text-transform: uppercase; letter-spacing: .05em; }
.z-pdf-open { margin-left: auto; color: var(--accent); text-decoration: none; font-size: 0.75rem; }
.z-pdf-iframe { width: 100%; height: 320px; display: block; border: none; background: var(--bg); }
.z-actions { display: flex; flex-direction: column; gap: 0.5rem; }
.z-original-note { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 0.875rem; }
.z-label { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--fg-subtle); margin-bottom: 0.375rem; }
.z-original-text { font-size: 0.875rem; color: var(--fg-muted); line-height: 1.6; margin: 0; white-space: pre-wrap; }

/* Buttons */
.z-btn { display: flex; align-items: center; gap: 0.5rem; justify-content: center; width: 100%; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 700; border: none; border-radius: 9px; cursor: pointer; transition: opacity 150ms, transform 150ms; font-family: var(--font-sans); }
.z-btn:hover { opacity: 0.85; transform: translateY(-1px); }
.z-btn-primary { background: var(--accent); color: var(--accent-fg); box-shadow: 0 0 20px var(--accent-glow); }
.z-btn-ghost { background: var(--surface-2); color: var(--fg-muted); border: 1px solid var(--border); }
.z-btn-ghost:hover { color: var(--fg); border-color: var(--border-str); }
.z-btn-danger { background: transparent; color: var(--fg-subtle); border: 1px solid var(--border); font-weight: 400; }
.z-btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; width: auto; }

/* Sub-tabs */
.z-sub-tabs { display: flex; gap: 0.25rem; margin-bottom: 1rem; }
.z-sub-tab { font-size: 0.8125rem; font-weight: 600; padding: 0.3125rem 0.75rem; border-radius: 6px; text-decoration: none; color: var(--fg-muted); border: 1px solid transparent; }
.z-sub-tab:hover { color: var(--fg); background: var(--surface-2); }
.z-sub-tab.is-active { background: var(--surface-2); border-color: var(--border); color: var(--fg); }

/* Content area */
.z-content { min-width: 0; }
.z-empty { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 4rem 2rem; color: var(--fg-muted); text-align: center; background: var(--surface); border: 1px dashed var(--border); border-radius: 14px; font-size: 0.9375rem; }
.z-empty-fail { border-color: rgba(239,68,68,0.4); color: var(--danger); }
.z-spinner { width: 32px; height: 32px; border: 3px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.z-raw { font-size: 0.8125rem; font-family: var(--font-mono); white-space: pre-wrap; word-break: break-word; color: var(--fg-muted); background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 1rem; line-height: 1.6; }

/* Prose */
.z-prose { font-size: 0.9375rem; line-height: 1.8; color: var(--fg); }
.z-prose h2 { font-size: 1.125rem; font-weight: 800; margin: 1.5rem 0 0.5rem; color: var(--fg); border-bottom: 1px solid var(--border-sub); padding-bottom: 0.25rem; }
.z-prose h3 { font-size: 1rem; font-weight: 700; margin: 1.25rem 0 0.375rem; color: var(--accent); }
.z-prose ul, .z-prose ol { padding-left: 1.5rem; margin: 0.5rem 0; }
.z-prose li { margin-bottom: 0.25rem; color: var(--fg); }
.z-prose strong { font-weight: 700; color: var(--fg); }
.z-prose p { margin: 0.5rem 0; color: var(--fg); }
.z-prose table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.875rem; }
.z-prose th { background: var(--surface-2); color: var(--fg); font-weight: 700; padding: 0.5rem 0.75rem; border: 1px solid var(--border); text-align: left; }
.z-prose td { padding: 0.5rem 0.75rem; border: 1px solid var(--border); color: var(--fg-muted); }
.z-prose code { font-family: var(--font-mono); font-size: 0.875em; background: var(--surface-2); padding: 0.125rem 0.375rem; border-radius: 4px; }
.z-formula-prose { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; }

/* Practice tab */
.z-practice-wrap, .z-insights-wrap { max-width: 720px; }
.z-tab-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.z-tab-title { font-size: 1.125rem; font-weight: 800; margin: 0; color: var(--fg); }
.z-tab-sub { font-size: 0.8125rem; color: var(--fg-muted); margin: 0.25rem 0 0; }
.z-questions { display: flex; flex-direction: column; gap: 1rem; }
.z-q-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.125rem; }
.z-q-meta { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.z-q-num { font-size: 0.6875rem; font-weight: 700; color: var(--fg-subtle); text-transform: uppercase; letter-spacing: .08em; }
.z-q-type { font-size: 0.6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; padding: 0.125rem 0.4rem; border-radius: 4px; background: var(--accent-glow); color: var(--accent); border: 1px solid rgba(245,158,11,0.3); }
.z-q-text { font-size: 0.9375rem; font-weight: 500; color: var(--fg); margin: 0 0 0.75rem; line-height: 1.5; }
.z-q-opts { list-style: none; padding: 0; margin: 0 0 0.75rem; display: flex; flex-direction: column; gap: 0.375rem; }
.z-q-opts li { font-size: 0.875rem; color: var(--fg-muted); padding: 0.4375rem 0.75rem; border-radius: 8px; border: 1px solid var(--border); }
.z-correct { color: #4ade80 !important; border-color: rgba(34,197,94,0.4) !important; background: rgba(34,197,94,0.08); font-weight: 600; }
.z-answer { margin-top: 0.5rem; }
.z-answer summary { font-size: 0.8125rem; color: #60a5fa; cursor: pointer; font-weight: 600; }
.z-answer p { margin: 0.5rem 0 0; font-size: 0.875rem; color: var(--fg); background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.25); border-radius: 8px; padding: 0.5rem 0.75rem; }

/* Insights tab */
.z-insight-block { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 1.25rem; margin-bottom: 1.25rem; }
.z-insight-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.z-insight-icon { width: 34px; height: 34px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.z-icon-saffron { background: var(--accent-glow); color: var(--accent); border: 1px solid rgba(245,158,11,0.3); }
.z-icon-blue { background: rgba(37,99,235,0.15); color: #60a5fa; border: 1px solid rgba(96,165,250,0.3); }
.z-icon-green { background: rgba(34,197,94,0.12); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
.z-insight-title { font-size: 1rem; font-weight: 800; margin: 0; color: var(--fg); }
.z-insight-sub { font-size: 0.75rem; color: var(--fg-muted); margin: 0.125rem 0 0; }
.z-insight-empty { color: var(--fg-subtle); font-size: 0.875rem; }
.ml-auto { margin-left: auto; }

/* Flashcards */
.z-flashcards { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem; }
.z-flashcard { height: 140px; cursor: pointer; perspective: 1000px; }
.z-fc-inner { position: relative; width: 100%; height: 100%; transform-style: preserve-3d; transition: transform 0.5s ease; border-radius: 12px; }
.z-flashcard.is-flipped .z-fc-inner { transform: rotateY(180deg); }
.z-fc-front, .z-fc-back { position: absolute; inset: 0; backface-visibility: hidden; border-radius: 12px; padding: 1rem; display: flex; flex-direction: column; justify-content: center; }
.z-fc-front { background: var(--surface-2); border: 1px solid var(--border); }
.z-fc-back { background: linear-gradient(135deg, rgba(245,158,11,0.12), rgba(245,158,11,0.06)); border: 1px solid rgba(245,158,11,0.3); transform: rotateY(180deg); }
.z-fc-front p, .z-fc-back p { margin: 0; font-size: 0.875rem; color: var(--fg); line-height: 1.5; }
.z-fc-type { font-size: 0.55rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--fg-subtle); margin-bottom: 0.375rem; }
.z-fc-hint { font-size: 0.6rem; color: var(--fg-subtle); margin-top: auto; padding-top: 0.5rem; }

/* Ask AI tab */
.z-ask-wrap { max-width: 680px; }
.z-ask-context { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: var(--fg-muted); background: rgba(96,165,250,0.08); border: 1px solid var(--border); border-radius: 8px; padding: 0.625rem 0.875rem; margin-bottom: 1.25rem; }
.z-chat { display: flex; flex-direction: column; gap: 0.875rem; min-height: 200px; max-height: 420px; overflow-y: auto; margin-bottom: 1rem; padding: 0.25rem 0; }
.z-bubble { display: flex; max-width: 88%; }
.z-bubble-ai { align-self: flex-start; }
.z-bubble-user { align-self: flex-end; flex-direction: row-reverse; }
.z-bubble-text { padding: 0.75rem 1rem; border-radius: 14px; font-size: 0.9rem; line-height: 1.6; }
.z-bubble-ai .z-bubble-text { background: var(--surface-2); border: 1px solid var(--border); color: var(--fg); border-bottom-left-radius: 4px; }
.z-bubble-user .z-bubble-text { background: var(--accent); color: var(--accent-fg); border-bottom-right-radius: 4px; font-weight: 500; }
.z-typing { display: flex; gap: 4px; align-items: center; height: 20px; }
.z-typing span { width: 6px; height: 6px; background: var(--fg-muted); border-radius: 50%; animation: blink 1.2s infinite; }
.z-typing span:nth-child(2) { animation-delay: 0.2s; }
.z-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes blink { 0%,80%,100% { opacity: 0.2; } 40% { opacity: 1; } }
.z-ask-form { display: flex; gap: 0.5rem; }
.z-ask-input { flex: 1; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.9375rem; color: var(--fg); font-family: var(--font-sans); outline: none; transition: border-color 150ms; }
.z-ask-input:focus { border-color: rgba(96,165,250,0.5); }
.z-ask-input::placeholder { color: var(--fg-subtle); }
.z-ask-btn { width: 44px; height: 44px; background: #2563eb; color: #fff; border: none; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; transition: opacity 150ms; }
.z-ask-btn:hover { opacity: 0.85; }
.z-ask-btn:disabled { opacity: 0.4; }
.z-ask-disabled { text-align: center; color: var(--fg-subtle); font-size: 0.875rem; padding: 2rem; background: var(--surface); border: 1px dashed var(--border); border-radius: 12px; }

/* ── PYQ Section ── */
.z-pyq-wrap { margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.75rem; }
.z-pyq-empty { margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem; color: var(--fg-subtle); font-size: 0.875rem; text-align: center; padding-bottom: 1rem; }
.z-pyq-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.z-pyq-title { font-family: var(--font-serif); font-size: 1.125rem; font-weight: 700; color: var(--fg); margin: 0; }
.z-pyq-sub { font-size: 0.75rem; color: var(--fg-muted); margin: 0.25rem 0 0; }
.z-pyq-badge { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; padding: 0.25rem 0.625rem; background: var(--accent-glow); border: 1px solid color-mix(in srgb, var(--accent) 30%, var(--border)); color: var(--accent); border-radius: 99px; flex-shrink: 0; }
.z-pyq-list { display: flex; flex-direction: column; gap: 1rem; }
.z-pyq-card { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 1rem 1.125rem; display: flex; flex-direction: column; gap: 0.75rem; transition: border-color 200ms; }
.z-pyq-got    { border-color: color-mix(in srgb, var(--success) 40%, var(--border)); background: color-mix(in srgb, var(--success) 4%, var(--surface-2)); }
.z-pyq-tricky { border-color: color-mix(in srgb, var(--danger) 40%, var(--border)); background: color-mix(in srgb, var(--danger) 4%, var(--surface-2)); }
.z-pyq-meta { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.z-pyq-year  { font-size: 0.6875rem; font-weight: 800; color: var(--accent); background: var(--accent-glow); padding: 0.125rem 0.5rem; border-radius: 4px; }
.z-pyq-topic { font-size: 0.6875rem; color: var(--fg-subtle); }
.z-pyq-type  { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--fg-subtle); background: var(--surface-3); padding: 0.1rem 0.4rem; border-radius: 3px; }
.z-pyq-status { font-size: 0.6875rem; font-weight: 700; padding: 0.1875rem 0.5rem; border-radius: 99px; margin-left: auto; }
.z-pyq-status-got    { color: var(--success); background: color-mix(in srgb, var(--success) 12%, transparent); }
.z-pyq-status-tricky { color: var(--danger); background: color-mix(in srgb, var(--danger) 12%, transparent); }
.z-pyq-q { font-size: 0.9375rem; color: var(--fg); line-height: 1.55; margin: 0; }
.z-pyq-opts { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.25rem; }
.z-pyq-opts li { font-size: 0.875rem; color: var(--fg-muted); padding: 0.1875rem 0; }
.z-pyq-details summary { list-style: none; font-size: 0.8125rem; color: var(--fg-subtle); cursor: pointer; }
.z-pyq-details summary::-webkit-details-marker { display: none; }
.z-pyq-details[open] summary { color: var(--fg-muted); margin-bottom: 0.5rem; }
.z-pyq-solution { font-size: 0.875rem; color: var(--fg-muted); line-height: 1.55; background: color-mix(in srgb, var(--success) 6%, var(--bg)); border-left: 3px solid var(--success); padding: 0.625rem 0.875rem; border-radius: 0 8px 8px 0; }
.z-pyq-solution strong { color: var(--success); display: block; margin-bottom: 0.25rem; }
.z-pyq-actions { display: flex; gap: 0.5rem; }
.z-pyq-btn { flex: 1; padding: 0.4375rem 0; font-size: 0.8125rem; font-weight: 700; border: 1px solid var(--border); border-radius: 8px; background: transparent; cursor: pointer; font-family: var(--font-sans); transition: all 150ms; color: var(--fg-muted); }
.z-pyq-btn-got:hover    { background: color-mix(in srgb, var(--success) 12%, transparent); border-color: var(--success); color: var(--success); }
.z-pyq-btn-tricky:hover { background: color-mix(in srgb, var(--danger) 12%, transparent); border-color: var(--danger); color: var(--danger); }
.z-pyq-btn.is-active-got    { background: color-mix(in srgb, var(--success) 15%, transparent); border-color: var(--success); color: var(--success); }
.z-pyq-btn.is-active-tricky { background: color-mix(in srgb, var(--danger) 15%, transparent); border-color: var(--danger); color: var(--danger); }
</style>
</x-layouts.student-shell>
