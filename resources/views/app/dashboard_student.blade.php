<x-layouts.student-shell title="My Notes — Zenith">
    <section class="sn-page">
        <div class="container-page sn-inner">

            {{-- Header --}}
            <header class="sn-header">
                <div>
                    <p class="sn-eyebrow">{{ now()->format('l · d F Y') }}</p>
                    <h1 class="sn-heading">
                        My Notes, <span style="color:var(--accent)">{{ explode(' ', auth()->user()->name)[0] }}</span>
                    </h1>
                </div>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center">
                    <a href="{{ route('app.student-notes.index') }}" class="sn-badge sn-badge-link">All Notes →</a>
                    <button onclick="copyParentLink(this)" class="sn-badge sn-badge-parent" title="Share dashboard with parents">
                        👨‍👩‍👧 Parents ko share karo
                    </button>
                </div>
            </header>

            {{-- ── Daily Log + Practice Widgets ── --}}
            <div class="snd-widgets">

                {{-- Daily Log --}}
                <div class="snd-card">
                    @if($todayLog)
                        {{-- Already logged today --}}
                        <div class="snd-card-head">
                            <span class="snd-card-icon">✅</span>
                            <div>
                                <p class="snd-card-label">Aaj ka log</p>
                                <p class="snd-card-sub">Done for today</p>
                            </div>
                        </div>
                        <div class="snd-log-summary">
                            @if($todayLog->studied_topics)
                                <div class="snd-log-row">
                                    <span class="snd-log-key">Padha</span>
                                    <span class="snd-log-val">{{ $todayLog->studied_topics }}</span>
                                </div>
                            @endif
                            @if($todayLog->hours_studied)
                                <div class="snd-log-row">
                                    <span class="snd-log-key">Ghante</span>
                                    <span class="snd-log-val">{{ $todayLog->hours_studied }} hrs</span>
                                </div>
                            @endif
                            @if($todayLog->mood)
                                <div class="snd-log-row">
                                    <span class="snd-log-key">Mood</span>
                                    <span class="snd-log-val" style="font-size:1.25rem">{{ $todayLog->moodEmoji() }}</span>
                                </div>
                            @endif
                            @if($todayLog->food)
                                <div class="snd-log-row">
                                    <span class="snd-log-key">Khaana</span>
                                    <span class="snd-log-val">{{ $todayLog->food }}</span>
                                </div>
                            @endif
                            @if($todayLog->expenses)
                                <div class="snd-log-row">
                                    <span class="snd-log-key">Kharch</span>
                                    <span class="snd-log-val">₹{{ number_format($todayLog->expenses, 0) }}</span>
                                </div>
                            @endif
                        </div>
                        {{-- Allow re-edit --}}
                        <details class="snd-edit-details">
                            <summary class="snd-edit-toggle">Edit log</summary>
                            @include('app.dashboard_student._log_form', ['todayLog' => $todayLog])
                        </details>
                    @else
                        {{-- Not logged yet --}}
                        <div class="snd-card-head">
                            <span class="snd-card-icon">📋</span>
                            <div>
                                <p class="snd-card-label">Aaj ka log</p>
                                <p class="snd-card-sub">2 min, fill karo abhi</p>
                            </div>
                        </div>
                        @include('app.dashboard_student._log_form', ['todayLog' => null])
                    @endif
                </div>

                {{-- Practice Widget --}}
                <div class="snd-card">
                    <div class="snd-card-head">
                        <span class="snd-card-icon">⚡</span>
                        <div>
                            <p class="snd-card-label">Aaj ka practice</p>
                            <p class="snd-card-sub">
                                @if($practiceNote)
                                    {{ Str::limit($practiceNote->title ?? 'Recent note', 35) }}
                                @else
                                    Scan a note to get questions
                                @endif
                            </p>
                        </div>
                    </div>

                    @if(count($practiceQuestions) > 0)
                        <div class="snd-qs">
                            @foreach($practiceQuestions as $i => $q)
                                <details class="snd-q">
                                    <summary class="snd-q-summary">
                                        <span class="snd-q-num">Q{{ $i + 1 }}</span>
                                        <span class="snd-q-text">{{ $q['question'] }}</span>
                                        <span class="snd-q-arrow">›</span>
                                    </summary>
                                    <div class="snd-q-answer">
                                        @if(isset($q['options']) && is_array($q['options']))
                                            <ul class="snd-q-opts">
                                                @foreach($q['options'] as $opt)
                                                    <li class="{{ isset($q['answer']) && str_starts_with($opt, $q['answer'].'.') ? 'snd-correct' : '' }}">{{ $opt }}</li>
                                                @endforeach
                                            </ul>
                                            @if(isset($q['answer']))
                                                <p class="snd-ans-label">Answer: <strong>{{ $q['answer'] }}</strong></p>
                                            @endif
                                        @else
                                            <p class="snd-ans-text">{{ $q['answer'] ?? '—' }}</p>
                                        @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>
                        <a href="{{ route('app.student-notes.show', [$practiceNote, 'tab' => 'practice']) }}"
                           class="snd-more-link">More questions →</a>
                    @else
                        <div class="snd-empty-qs">
                            <p>Koi note scan nahi hua abhi tak.</p>
                            <p style="font-size:0.8125rem;color:var(--fg-subtle);margin-top:0.25rem">Upload → Scan karo → Practice milega</p>
                        </div>
                    @endif
                </div>

            </div>
            {{-- ── End Widgets ── --}}

            {{-- Upload form --}}
            <div class="sn-upload-card">

                {{-- Input type toggle --}}
                <div class="sn-input-tabs">
                    <button type="button" class="sn-input-tab is-active" id="tab-upload" onclick="switchInputTab('upload')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Photo / PDF
                    </button>
                    <button type="button" class="sn-input-tab" id="tab-youtube" onclick="switchInputTab('youtube')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        YouTube Video
                    </button>
                </div>

                {{-- Photo / PDF form --}}
                <div id="panel-upload">
                <form method="POST" action="{{ route('app.student-notes.store') }}" enctype="multipart/form-data" id="sn-form">
                    @csrf
                    <div class="sn-form-row">
                        <div class="sn-drop-area" id="sn-drop">
                            <input type="file" name="image" id="sn-file" accept="image/*,.pdf" class="sn-file-input">
                            <label for="sn-file" class="sn-drop-label" id="sn-drop-label">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                <span id="sn-file-name">Photo or PDF upload</span>
                                <span id="sn-file-hint" style="font-size:0.7rem;color:var(--fg-muted);margin-top:0.25rem">JPG · PNG · PDF — max 20 MB</span>
                            </label>
                            <img id="sn-preview" class="sn-preview" style="display:none" alt="Preview">
                            <div id="sn-pdf-preview" class="sn-pdf-preview" style="display:none">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
                                <span id="sn-pdf-name"></span>
                            </div>
                        </div>
                        <div class="sn-form-fields">
                            <div class="sn-two-col">
                                <input type="text" name="subject" placeholder="Subject (e.g. Physics)" class="sn-input" maxlength="100" list="sn-subjects-list">
                                <datalist id="sn-subjects-list">
                                    @foreach($allSubjects ?? [] as $s)<option value="{{ $s }}">@endforeach
                                    <option value="Physics"><option value="Chemistry"><option value="Maths">
                                    <option value="Biology"><option value="Physical Chemistry">
                                    <option value="Organic Chemistry"><option value="Inorganic Chemistry">
                                    <option value="Mechanics"><option value="Electrostatics"><option value="Calculus">
                                    <option value="Algebra"><option value="Coordinate Geometry"><option value="Other">
                                </datalist>
                                <input type="text" name="class_name" placeholder="Class (e.g. Class 11)" class="sn-input" maxlength="100" list="sn-classes-list">
                                <datalist id="sn-classes-list">
                                    @foreach($allClasses ?? [] as $c)<option value="{{ $c }}">@endforeach
                                    <option value="Class 11"><option value="Class 12"><option value="Dropper">
                                </datalist>
                            </div>
                            <input type="text" name="title" placeholder="Title (optional)" class="sn-input" maxlength="200">
                            <textarea name="note_text" placeholder="Add a note (optional)…" class="sn-textarea" rows="3" maxlength="5000"></textarea>
                            <button type="submit" class="sn-submit">Save Note</button>
                        </div>
                    </div>
                    @error('file')<p class="sn-error">{{ $message }}</p>@enderror
                </form>
                </div>

                {{-- YouTube form --}}
                <div id="panel-youtube" style="display:none">
                <form method="POST" action="{{ route('app.student-notes.from-youtube') }}" id="yt-form">
                    @csrf
                    <div class="sn-form-row sn-yt-row">
                        <div class="sn-yt-url-wrap">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            <input type="url" name="youtube_url" id="yt-url" placeholder="Paste YouTube URL (PW / Unacademy / any lecture…)" class="sn-input sn-yt-input" required>
                        </div>
                        <div class="sn-form-fields">
                            <div class="sn-two-col">
                                <input type="text" name="subject" placeholder="Subject (e.g. Physics)" class="sn-input" maxlength="100" list="sn-subjects-list">
                                <input type="text" name="class_name" placeholder="Class (e.g. Class 11)" class="sn-input" maxlength="100" list="sn-classes-list">
                            </div>
                            <button type="submit" class="sn-submit sn-submit-yt" id="yt-submit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                Fetch &amp; Organise
                            </button>
                            <p class="sn-yt-hint">Transcript is fetched automatically — no download needed. Works with Hindi &amp; English lectures.</p>
                        </div>
                    </div>
                    @error('youtube_url')<p class="sn-error">{{ $message }}</p>@enderror
                </form>
                </div>

            </div>

            {{-- Filter bar --}}
            @if($allSubjects->isNotEmpty() || $allClasses->isNotEmpty())
            <div class="sn-filters">
                <a href="{{ route('app.dashboard') }}" class="sn-filter-pill {{ !$filterSubject && !$filterClass ? 'is-active' : '' }}">All</a>
                @foreach($allSubjects as $s)
                    <a href="{{ route('app.dashboard', ['subject' => $s]) }}" class="sn-filter-pill {{ $filterSubject === $s ? 'is-active' : '' }}">{{ $s }}</a>
                @endforeach
                @foreach($allClasses as $c)
                    <a href="{{ route('app.dashboard', ['class_name' => $c]) }}" class="sn-filter-pill sn-filter-class {{ $filterClass === $c ? 'is-active' : '' }}">{{ $c }}</a>
                @endforeach
            </div>
            @endif

            {{-- Notes gallery --}}
            <div class="sn-gallery-header">
                <h2 class="sn-section-title">
                    @if($filterSubject) {{ $filterSubject }}
                    @elseif($filterClass) {{ $filterClass }}
                    @else Saved Notes
                    @endif
                </h2>
                <span class="sn-count">{{ $studentNotes->count() }} {{ Str::plural('note', $studentNotes->count()) }}</span>
            </div>

            @if($studentNotes->isEmpty())
                <div class="sn-empty">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <p>No notes yet — upload your first photo above.</p>
                </div>
            @else
                <div class="sn-grid">
                    @foreach($studentNotes as $note)
                        <a href="{{ route('app.student-notes.show', $note) }}" class="sn-card" style="text-decoration:none;color:inherit">
                            @if($note->image_path)
                                @php $isPdf = str_ends_with(strtolower($note->image_path), '.pdf'); @endphp
                                <div class="sn-card-img-wrap">
                                    @if($isPdf)
                                        <div class="sn-card-pdf-thumb">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
                                            <span>PDF</span>
                                        </div>
                                    @else
                                        <img src="{{ asset('storage/'.$note->image_path) }}" alt="{{ $note->title ?? 'Note' }}" class="sn-card-img" loading="lazy">
                                    @endif
                                </div>
                            @endif
                            <div class="sn-card-body">
                                @if($note->subject || $note->class_name)
                                <div class="sn-card-tags">
                                    @if($note->subject)<span class="sn-tag">{{ $note->subject }}</span>@endif
                                    @if($note->class_name)<span class="sn-tag sn-tag-class">{{ $note->class_name }}</span>@endif
                                </div>
                                @endif
                                @if($note->title)
                                    <h3 class="sn-card-title">{{ $note->title }}</h3>
                                @endif
                                @if($note->note_text)
                                    <p class="sn-card-text">{{ Str::limit($note->note_text, 120) }}</p>
                                @endif
                                <div class="sn-card-foot">
                                    <span class="sn-card-date">{{ $note->created_at->diffForHumans() }}</span>
                                    @if($note->ai_status === 'done')
                                        <span style="font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--success)">✓ Scanned</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

        </div>
    </section>

    <style>
        .sn-page { background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 4%, var(--bg)) 0%, var(--bg) 300px); min-height: 100vh; }
        .sn-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 1000px; }

        .sn-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .sn-eyebrow { font-size: 0.75rem; color: var(--fg-muted); text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 0.375rem; }
        .sn-heading { font-family: var(--font-serif); font-size: clamp(1.75rem, 3.5vw, 2.5rem); font-weight: 500; color: var(--fg); line-height: 1.1; letter-spacing: -0.02em; margin: 0; }
        .sn-badge { display: inline-flex; align-items: center; padding: 0.3125rem 0.75rem; background: color-mix(in srgb, var(--success) 14%, var(--surface)); border: 1px solid color-mix(in srgb, var(--success) 35%, var(--border)); color: var(--success); border-radius: 99px; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; flex-shrink: 0; margin-top: 0.5rem; }
        .sn-badge-link { text-decoration: none; transition: opacity 150ms; }
        .sn-badge-link:hover { opacity: 0.8; }
        .sn-badge-parent { background: color-mix(in srgb, #f59e0b 14%, var(--surface)); border: 1px solid color-mix(in srgb, #f59e0b 40%, var(--border)); color: #b45309; cursor: pointer; font-family: inherit; transition: opacity 150ms; }
        .sn-badge-parent:hover { opacity: 0.8; }

        /* ── Widgets row ── */
        .snd-widgets { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.75rem; }
        @media (min-width: 640px) { .snd-widgets { grid-template-columns: 1fr 1fr; } }

        .snd-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }

        .snd-card-head { display: flex; align-items: flex-start; gap: 0.875rem; }
        .snd-card-icon { font-size: 1.5rem; flex-shrink: 0; line-height: 1; margin-top: 0.125rem; }
        .snd-card-label { font-family: var(--font-serif); font-size: 1rem; font-weight: 700; color: var(--fg); margin: 0; }
        .snd-card-sub { font-size: 0.8rem; color: var(--fg-muted); margin: 0.125rem 0 0; }

        /* Log form */
        .snd-log-form { display: flex; flex-direction: column; gap: 0.625rem; }
        .snd-log-input { background: var(--bg); border: 1px solid var(--border-sub); border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.875rem; color: var(--fg); outline: none; width: 100%; font-family: inherit; transition: border-color 150ms; }
        .snd-log-input:focus { border-color: color-mix(in srgb, var(--accent) 60%, var(--border)); }
        .snd-log-input::placeholder { color: var(--fg-subtle); }

        .snd-mood-row { display: flex; gap: 0.375rem; align-items: center; }
        .snd-mood-label { font-size: 0.75rem; color: var(--fg-muted); margin-right: 0.25rem; white-space: nowrap; }
        .snd-mood-radio { display: none; }
        .snd-mood-btn { font-size: 1.375rem; cursor: pointer; opacity: 0.35; transition: opacity 150ms, transform 150ms; line-height: 1; }
        .snd-mood-radio:checked + .snd-mood-btn { opacity: 1; transform: scale(1.2); }
        .snd-mood-btn:hover { opacity: 0.75; }

        .snd-two { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }

        .snd-log-submit { background: var(--accent); color: var(--accent-fg); border: none; border-radius: 8px; padding: 0.5625rem 1rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: opacity 150ms; font-family: inherit; }
        .snd-log-submit:hover { opacity: 0.85; }

        /* Log summary */
        .snd-log-summary { display: flex; flex-direction: column; gap: 0.375rem; }
        .snd-log-row { display: flex; gap: 0.625rem; align-items: baseline; }
        .snd-log-key { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--fg-subtle); flex-shrink: 0; width: 3.5rem; }
        .snd-log-val { font-size: 0.875rem; color: var(--fg); }

        .snd-edit-details summary { list-style: none; }
        .snd-edit-details summary::-webkit-details-marker { display: none; }
        .snd-edit-toggle { font-size: 0.75rem; color: var(--fg-subtle); cursor: pointer; text-decoration: underline; text-underline-offset: 2px; }
        .snd-edit-toggle:hover { color: var(--fg-muted); }
        .snd-edit-details[open] .snd-edit-toggle { margin-bottom: 0.75rem; display: block; }

        /* Practice Qs */
        .snd-qs { display: flex; flex-direction: column; gap: 0.5rem; }
        .snd-q { border: 1px solid var(--border-sub); border-radius: 10px; overflow: hidden; }
        .snd-q-summary { display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.625rem 0.75rem; cursor: pointer; list-style: none; }
        .snd-q-summary::-webkit-details-marker { display: none; }
        .snd-q-summary:hover { background: color-mix(in srgb, var(--fg) 3%, transparent); }
        .snd-q-num { font-size: 0.6rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent); background: var(--accent-glow); padding: 0.125rem 0.375rem; border-radius: 4px; flex-shrink: 0; margin-top: 0.125rem; }
        .snd-q-text { font-size: 0.8125rem; color: var(--fg); line-height: 1.45; flex: 1; }
        .snd-q-arrow { font-size: 1rem; color: var(--fg-subtle); flex-shrink: 0; transition: transform 200ms; }
        .snd-q[open] .snd-q-arrow { transform: rotate(90deg); }
        .snd-q-answer { padding: 0.625rem 0.75rem; border-top: 1px solid var(--border-sub); background: color-mix(in srgb, var(--success) 5%, var(--bg)); }
        .snd-q-opts { list-style: none; padding: 0; margin: 0 0 0.375rem; display: flex; flex-direction: column; gap: 0.25rem; }
        .snd-q-opts li { font-size: 0.8125rem; color: var(--fg-muted); padding: 0.1875rem 0; }
        .snd-correct { color: var(--success) !important; font-weight: 700; }
        .snd-ans-label { font-size: 0.8125rem; font-weight: 700; color: var(--success); margin: 0; }
        .snd-ans-text { font-size: 0.8125rem; color: var(--fg-muted); margin: 0; line-height: 1.5; }

        .snd-more-link { font-size: 0.8125rem; color: var(--accent); text-decoration: none; font-weight: 600; }
        .snd-more-link:hover { text-decoration: underline; }

        .snd-empty-qs { text-align: center; padding: 1rem 0; color: var(--fg-muted); font-size: 0.875rem; }

        /* Upload card */
        .sn-upload-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; }
        .sn-form-row { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
        @media (min-width: 640px) { .sn-form-row { grid-template-columns: 220px 1fr; } }

        .sn-drop-area { position: relative; border: 2px dashed var(--border); border-radius: 12px; overflow: hidden; min-height: 160px; display: flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--accent) 3%, var(--bg)); cursor: pointer; transition: border-color 150ms; }
        .sn-drop-area:hover { border-color: color-mix(in srgb, var(--accent) 50%, var(--border)); }
        .sn-file-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .sn-drop-label { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; color: var(--fg-muted); font-size: 0.8125rem; text-align: center; padding: 1rem; pointer-events: none; }
        .sn-preview { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }

        .sn-form-fields { display: flex; flex-direction: column; gap: 0.75rem; }
        .sn-input { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 0.625rem 0.875rem; font-size: 0.9375rem; color: var(--fg); outline: none; transition: border-color 150ms; width: 100%; }
        .sn-input:focus { border-color: color-mix(in srgb, var(--accent) 60%, var(--border)); }
        .sn-textarea { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 0.625rem 0.875rem; font-size: 0.9375rem; color: var(--fg); outline: none; resize: vertical; transition: border-color 150ms; font-family: inherit; width: 100%; }
        .sn-textarea:focus { border-color: color-mix(in srgb, var(--accent) 60%, var(--border)); }
        .sn-submit { background: var(--primary); color: var(--primary-fg); border: none; border-radius: 9px; padding: 0.75rem 1.25rem; font-size: 0.9375rem; font-weight: 600; cursor: pointer; transition: opacity 150ms; }
        .sn-submit:hover { opacity: 0.88; }
        .sn-error { color: var(--danger); font-size: 0.8125rem; margin: 0.25rem 0 0; }

        /* Input type tabs */
        .sn-input-tabs { display: flex; gap: 0.25rem; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 0.25rem; margin-bottom: 1rem; width: fit-content; }
        .sn-input-tab { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; font-size: 0.8125rem; font-weight: 500; color: var(--fg-muted); background: none; border: none; border-radius: 7px; cursor: pointer; transition: all 150ms; }
        .sn-input-tab:hover { color: var(--fg); }
        .sn-input-tab.is-active { background: var(--surface); color: var(--fg); font-weight: 600; box-shadow: 0 1px 4px -1px rgba(0,0,0,0.12); }

        /* YouTube form */
        .sn-yt-row { grid-template-columns: 1fr !important; gap: 0.875rem !important; }
        .sn-yt-url-wrap { display: flex; align-items: center; gap: 0.625rem; background: var(--bg); border: 1.5px solid color-mix(in srgb, #ef4444 45%, var(--border)); border-radius: 10px; padding: 0.625rem 0.875rem; }
        .sn-yt-url-wrap svg { color: #ef4444; flex-shrink: 0; }
        .sn-yt-input { border: none !important; padding: 0 !important; background: transparent !important; flex: 1; font-size: 0.9375rem; }
        .sn-yt-input:focus { outline: none; }
        .sn-submit-yt { background: #ef4444; display: inline-flex; align-items: center; gap: 0.5rem; }
        .sn-submit-yt:hover { background: #dc2626; opacity: 1 !important; }
        .sn-yt-hint { font-size: 0.75rem; color: var(--fg-muted); margin: 0; }

        /* Gallery header */
        .sn-gallery-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
        .sn-section-title { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--fg-muted); margin: 0; }
        .sn-count { font-size: 0.75rem; color: var(--fg-muted); background: var(--surface); border: 1px solid var(--border); border-radius: 99px; padding: 0.125rem 0.5rem; }

        .sn-empty { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; padding: 3rem 1rem; color: var(--fg-muted); text-align: center; font-size: 0.9375rem; }

        /* Notes grid */
        .sn-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
        .sn-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; transition: transform 200ms ease-out, box-shadow 200ms ease-out; }
        .sn-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -8px color-mix(in srgb, var(--primary) 18%, transparent); }
        .sn-card-img-wrap { display: block; overflow: hidden; aspect-ratio: 4/3; background: var(--bg); }
        .sn-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 300ms ease-out; }
        .sn-card:hover .sn-card-img { transform: scale(1.03); }
        .sn-card-body { padding: 0.875rem; display: flex; flex-direction: column; gap: 0.375rem; flex: 1; }
        .sn-card-title { font-size: 0.9375rem; font-weight: 600; color: var(--fg); margin: 0; }
        .sn-card-text { font-size: 0.8125rem; color: var(--fg-muted); margin: 0; line-height: 1.5; }
        .sn-card-foot { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 0.5rem; }
        .sn-card-date { font-size: 0.6875rem; color: var(--fg-muted); }

        /* Filter pills */
        .sn-filters { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem; }
        .sn-filter-pill { display: inline-flex; align-items: center; padding: 0.3125rem 0.75rem; border: 1px solid var(--border); border-radius: 99px; font-size: 0.75rem; font-weight: 500; color: var(--fg-muted); text-decoration: none; background: var(--surface); transition: border-color 150ms, color 150ms; }
        .sn-filter-pill:hover { border-color: color-mix(in srgb, var(--accent) 50%, var(--border)); color: var(--fg); }
        .sn-filter-pill.is-active { background: color-mix(in srgb, var(--accent) 14%, var(--surface)); border-color: color-mix(in srgb, var(--accent) 50%, var(--border)); color: var(--accent); font-weight: 600; }
        .sn-filter-class.is-active { background: color-mix(in srgb, var(--success) 12%, var(--surface)); border-color: color-mix(in srgb, var(--success) 40%, var(--border)); color: var(--success); }

        /* Card tags */
        .sn-card-tags { display: flex; flex-wrap: wrap; gap: 0.375rem; margin-bottom: 0.25rem; }
        .sn-tag { font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 0.125rem 0.5rem; border-radius: 99px; background: color-mix(in srgb, var(--accent) 12%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, var(--border)); color: var(--accent); }
        .sn-tag-class { background: color-mix(in srgb, var(--success) 10%, transparent); border-color: color-mix(in srgb, var(--success) 30%, var(--border)); color: var(--success); }

        .sn-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        @media (max-width: 480px) { .sn-two-col { grid-template-columns: 1fr; } }

        .sn-pdf-preview { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; background: color-mix(in srgb, var(--accent) 8%, var(--surface)); color: var(--accent); font-size: 0.8125rem; font-weight: 600; padding: 1rem; text-align: center; }
        .sn-card-pdf-thumb { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.375rem; background: color-mix(in srgb, var(--accent) 7%, var(--surface)); color: var(--accent); font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; }
    </style>

    <script>
        const fileInput  = document.getElementById('sn-file');
        const preview    = document.getElementById('sn-preview');
        const pdfPreview = document.getElementById('sn-pdf-preview');
        const pdfName    = document.getElementById('sn-pdf-name');
        const fileName   = document.getElementById('sn-file-name');
        const dropLabel  = document.getElementById('sn-drop-label');

        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) return;
            fileName.textContent = file.name;
            if (file.type === 'application/pdf') {
                preview.style.display    = 'none';
                pdfName.textContent      = file.name;
                pdfPreview.style.display = 'flex';
                dropLabel.style.display  = 'none';
            } else {
                pdfPreview.style.display = 'none';
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src             = e.target.result;
                    preview.style.display   = 'block';
                    dropLabel.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Parent link copy
        async function copyParentLink(btn) {
            try {
                const res  = await fetch('{{ route('app.parent.share-link') }}');
                const data = await res.json();
                await navigator.clipboard.writeText(data.url);
                const orig = btn.textContent;
                btn.textContent = '✅ Link copy ho gaya!';
                setTimeout(() => btn.textContent = orig, 3000);
            } catch(e) {
                alert('Copy failed — try again.');
            }
        }

        // YouTube tab switching
        function switchInputTab(tab) {
            document.getElementById('panel-upload').style.display  = tab === 'upload'  ? '' : 'none';
            document.getElementById('panel-youtube').style.display = tab === 'youtube' ? '' : 'none';
            document.getElementById('tab-upload').classList.toggle('is-active',  tab === 'upload');
            document.getElementById('tab-youtube').classList.toggle('is-active', tab === 'youtube');
        }

        // Show spinner on YouTube submit
        document.getElementById('yt-form')?.addEventListener('submit', function() {
            const btn = document.getElementById('yt-submit');
            btn.disabled = true;
            btn.textContent = 'Fetching transcript…';
        });

        // Auto-switch to YouTube tab if there's a youtube_url error
        @if($errors->has('youtube_url'))
            switchInputTab('youtube');
        @endif
    </script>
</x-layouts.student-shell>
