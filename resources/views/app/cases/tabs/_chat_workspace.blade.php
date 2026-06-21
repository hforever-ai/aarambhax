{{-- Shared chat workspace partial — used by brainstorm / bahas / cross-exam / samjhao
     LEFT: docs in scope + meta + quick actions
     RIGHT: conversation thread + sticky composer

     Params from calling view ($config array):
       title          — empty-state heading
       subtitle       — empty-state copy under heading (HTML allowed inside the
                        view via {!! !!} since it's blade-controlled, not user input)
       placeholder    — composer placeholder
       quick_actions  — [['icon'=>'📋','label'=>'…','prompt'=>'…'], …]
       tier_label     — e.g. 'FREE tier' / 'PAID tier'
       tier_color_var — CSS var name without --: 'success' or 'accent' or 'fg-muted'
       send_url       — POST endpoint, defaults to existing chat route
--}}
@php
    $isApproved = auth()->user()->isApproved() || auth()->user()->isAdmin();
    $hasDocs = $documents->isNotEmpty();
    $hasMessages = $messages->isNotEmpty();

    $config = $config ?? [];
    $title = $config['title'] ?? 'Talk through this case with Aarambh';
    $subtitle = $config['subtitle'] ?? 'Free tier · PII-redacted · ₹0 per turn.<br>Pick documents on the left to focus the conversation.';
    $placeholder = $config['placeholder'] ?? 'Ask anything about this case…';
    // Resolve category-aware quick actions.
    // New format: associative array with keys like '_identify', '_all', 'criminal', 'civil', etc.
    // Legacy format: flat sequential array of actions (still supported).
    $quickActionsConfig = $config['quick_actions'] ?? [];
    $caseCategory = $case->category ?? '';
    if (!empty($quickActionsConfig) && array_key_exists('_all', $quickActionsConfig)) {
        $allActions = $quickActionsConfig['_all'] ?? [];
        if (empty($caseCategory)) {
            $quickActions = array_merge($quickActionsConfig['_identify'] ?? [], $allActions);
        } else {
            $quickActions = array_merge($quickActionsConfig[$caseCategory] ?? $quickActionsConfig['_all'] ?? [], $allActions);
        }
    } else {
        $quickActions = $quickActionsConfig;
    }
    $tierLabel = $config['tier_label'] ?? 'FREE tier';
    $tierColorVar = $config['tier_color_var'] ?? 'success';
    $sendUrl = $config['send_url'] ?? route('app.cases.chat.send', $case);
    $showLangPicker = $config['show_lang_picker'] ?? false;

    // Smart language default: HC + SC → English; all other forums → Hindi.
    // Draft tab passes its own default; chat tabs use this forum-based logic.
    $hcForums = ['cg_hc', 'sc'];
    $forumDefaultLang = in_array($case->forum ?? '', $hcForums) ? 'en' : 'hi';
    $defaultLang = $config['default_lang'] ?? $forumDefaultLang;

    // Chat tabs offer Hinglish; Draft tab keeps en/hi/bilingual only (court-filable).
    $isDraftTab = ($conversation->kind ?? '') === 'draft';
    $langOptions = $isDraftTab
        ? [['value' => 'en', 'label' => 'English'], ['value' => 'hi', 'label' => 'हिन्दी'], ['value' => 'bilingual', 'label' => 'Bilingual']]
        : [['value' => 'en', 'label' => 'English'], ['value' => 'hi', 'label' => 'हिन्दी (Hindi)'], ['value' => 'hinglish', 'label' => 'Hinglish']];

    // Per-mode persona meta — heading icon + description + tone tip
    $kind = $conversation->kind ?? 'brainstorm';
    $personas = [
        'brainstorm' => [
            'name'    => 'Brainstorm',
            'tagline' => 'Open exploration, no commitments',
            'tone'    => 'Casual + speculative. Aarambh thinks out loud with you.',
            'tips'    => [
                'Throw rough thoughts — Aarambh refines them',
                'Ask "what would opposite party argue?" early',
                'Convert to Draft when a direction emerges',
            ],
        ],
        'bahas' => [
            'name'    => 'Bahas',
            'tagline' => 'Hearing prep — opening, key paras, replies',
            'tone'    => 'Court-room voice. Cite section + para. Anticipate questions.',
            'tips'    => [
                'Ask for opening submission first, then expansions',
                'Request "what will judge ask" for stress test',
                'Rehearse rebuttals to opp counsel\'s likely lines',
            ],
        ],
        'cross_exam' => [
            'name'    => 'Cross-Exam',
            'tagline' => 'Witness questioning strategy',
            'tone'    => 'Tactical. Trap construction + sequence design.',
            'tips'    => [
                'Identify the witness\'s contradictions first',
                'Ask for question sequences, not single Qs',
                'Request foundation questions before traps',
            ],
        ],
        'samjhao' => [
            'name'    => 'Samjhao',
            'tagline' => 'Plain-language explanation for client',
            'tone'    => 'Hindi/Hinglish. Avoid legalese. Analogies welcome.',
            'tips'    => [
                'Explain section in 2-3 sentences first',
                'Ask "in simple words for the client" explicitly',
                'Request next-step list at the end',
            ],
        ],
        'analysis' => [
            'name'    => 'Analysis',
            'tagline' => 'Strategic case analysis',
            'tone'    => 'Senior-counsel voice. Risks + options + recommendation.',
            'tips'    => [
                'Provide ALL documents before asking',
                'Ask for 3-5 legal theories, ranked by viability',
                'Request gaps/missing-facts list',
            ],
        ],
        'draft' => [
            'name'    => 'Draft',
            'tagline' => 'Court-filable pleadings',
            'tone'    => 'Formal pleading register. Para-wise. Section citations.',
            'tips'    => [
                'Specify forum + draft type up front',
                '[TO BE FILLED] markers — review every one',
                'Ask for tightening pass before filing',
            ],
        ],
    ];
    $persona = $personas[$kind] ?? $personas['brainstorm'];
@endphp

<div class="bs-shell">
    {{-- LEFT PANE — context --}}
    <aside class="bs-context">
        {{-- Card 0 — Persona / mode banner --}}
        <div class="bs-card bs-card-persona">
            <div class="bs-persona-head">
                <span class="bs-persona-mark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <div class="bs-persona-meta">
                    <div class="bs-persona-name">{{ $persona['name'] }}</div>
                    <div class="bs-persona-tagline">{{ $persona['tagline'] }}</div>
                </div>
            </div>
            <p class="bs-persona-tone">{{ $persona['tone'] }}</p>
        </div>

        {{-- Card 1 — Documents in scope --}}
        <div class="bs-card bs-card-docs">
            <h3 class="bs-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Documents in scope
                @if($hasDocs)<span class="bs-card-count">{{ $documents->count() }}</span>@endif
            </h3>
            @if(! $hasDocs)
                <div class="bs-card-empty">
                    <svg class="bs-card-empty-icon" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 14 H38 L48 24 V52 H16 Z"/>
                        <path d="M38 14 V24 H48"/>
                        <path d="M22 32 H40 M22 38 H40 M22 44 H32" opacity="0.55"/>
                    </svg>
                    <p class="bs-card-empty-text">No documents uploaded yet</p>
                    <a href="{{ route('app.cases.tab.documents', $case) }}" class="bs-card-empty-cta">
                        Upload first document
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            @else
                <ul class="bs-doc-list" id="bs-doc-list">
                    @foreach($documents as $doc)
                        <li>
                            <label class="bs-doc-row">
                                <input type="checkbox" class="bs-doc-check" data-doc-id="{{ $doc->id }}" checked>
                                <div class="bs-doc-meta">
                                    <div class="bs-doc-name">{{ \Illuminate\Support\Str::limit($doc->original_filename, 32) }}</div>
                                    <div class="bs-doc-type">
                                        @if($doc->detected_doc_type)
                                            {{ str_replace('_', ' ', $doc->detected_doc_type) }}
                                        @else
                                            <span style="opacity: 0.5;">document</span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </li>
                    @endforeach
                </ul>
                <p class="bs-doc-count" id="bs-doc-count">{{ $documents->count() }} of {{ $documents->count() }} in scope</p>
            @endif
        </div>

        {{-- Card 2 — Session info --}}
        <div class="bs-card bs-card-session">
            <h3 class="bs-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Session
            </h3>
            <div class="bs-session-stats">
                <div class="bs-stat">
                    <span class="bs-stat-label">Started</span>
                    <span class="bs-stat-value">{{ $conversation->created_at->diffForHumans(null, true) }} ago</span>
                </div>
                <div class="bs-stat">
                    <span class="bs-stat-label">Messages</span>
                    <span class="bs-stat-value" id="bs-msg-count">{{ $messages->count() }}</span>
                </div>
                <div class="bs-stat">
                    <span class="bs-stat-label">Tier</span>
                    <span class="bs-tier-chip bs-tier-{{ $tierColorVar }}">{{ $tierLabel }}</span>
                </div>
            </div>
        </div>

        {{-- Card 3 — Quick actions --}}
        @if($hasDocs && $isApproved)
            <div class="bs-card bs-card-actions">
                <h3 class="bs-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.663 17h4.673M12 3v1m0 0a6 6 0 0 1 4.486 9.984c-.348.503-.486 1.119-.486 1.749V17H8v-1.267c0-.63-.138-1.246-.486-1.749A6 6 0 0 1 12 4z"/></svg>
                    Quick actions
                </h3>
                <ul class="bs-quick-list">
                    @foreach($quickActions as $qa)
                        <li>
                            <button type="button" class="bs-quick-btn" data-prompt="{{ $qa['prompt'] }}">
                                <span class="bs-quick-icon">{{ $qa['icon'] }}</span>
                                <span>{{ $qa['label'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Card 4 — Tips for this mode --}}
        <div class="bs-card bs-card-tips">
            <h3 class="bs-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Tips for {{ strtolower($persona['name']) }}
            </h3>
            <ul class="bs-tip-list">
                @foreach($persona['tips'] as $t)
                    <li>
                        <span class="bs-tip-bullet" aria-hidden="true"></span>
                        <span>{{ $t }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Card 5 — Privacy + safety reassurance --}}
        <div class="bs-card bs-card-privacy">
            <h3 class="bs-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                Privacy
            </h3>
            <ul class="bs-privacy-list">
                <li><span class="bs-privacy-dot bs-privacy-on"></span>Documents stay on your account only</li>
                <li><span class="bs-privacy-dot bs-privacy-on"></span>POCSO / minor names auto-redacted</li>
                <li><span class="bs-privacy-dot bs-privacy-on"></span>Per-user quota: 60/hr · 200/day</li>
            </ul>
        </div>
    </aside>

    {{-- RIGHT PANE — chat --}}
    <main class="bs-chat">
        <div class="bs-thread" id="bs-thread" aria-live="polite">
            @if(! $hasMessages)
                {{-- Empty state --}}
                <div class="bs-empty">
                    {{-- Soft watermark sigil behind everything — Aarambh "A" inside a crest --}}
                    <svg class="bs-empty-watermark" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="0.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M100 16 L172 50 V102 C172 142 140 174 100 184 C60 174 28 142 28 102 V50 Z"/>
                        <path d="M100 60 L122 130 H78 Z M88 110 H112"/>
                    </svg>
                    {{-- Scales of justice crest — primary mark --}}
                    <div class="bs-empty-mark" aria-hidden="true">
                        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            {{-- Central pillar --}}
                            <line x1="32" y1="14" x2="32" y2="50"/>
                            {{-- Crossbar --}}
                            <line x1="14" y1="20" x2="50" y2="20"/>
                            {{-- Top knob --}}
                            <circle cx="32" cy="14" r="2.5" fill="currentColor"/>
                            {{-- Base --}}
                            <path d="M22 50 L42 50"/>
                            <path d="M26 54 L38 54"/>
                            {{-- Left pan + chains --}}
                            <line x1="18" y1="20" x2="14" y2="30" stroke-dasharray="0"/>
                            <line x1="18" y1="20" x2="22" y2="30" stroke-dasharray="0"/>
                            <path d="M11 30 Q18 38 25 30 Z" fill="color-mix(in srgb, currentColor 12%, transparent)"/>
                            {{-- Right pan + chains --}}
                            <line x1="46" y1="20" x2="42" y2="30"/>
                            <line x1="46" y1="20" x2="50" y2="30"/>
                            <path d="M39 30 Q46 38 53 30 Z" fill="color-mix(in srgb, currentColor 12%, transparent)"/>
                        </svg>
                    </div>
                    <h2 class="bs-empty-title">{{ $title }}</h2>
                    <p class="bs-empty-sub">{!! $subtitle !!}</p>
                    @if($hasDocs && $isApproved)
                        <p class="bs-empty-hint">Try asking…</p>
                        <div class="bs-empty-prompts">
                            @foreach(array_slice($quickActions, 0, 3) as $qa)
                                <button type="button" class="bs-empty-prompt-card bs-quick-btn" data-prompt="{{ $qa['prompt'] }}">
                                    <span class="bs-quick-icon">{{ $qa['icon'] }}</span>
                                    <span>{{ $qa['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @elseif(! $hasDocs)
                        <p class="bs-empty-hint" style="margin-top: 1.5rem;">
                            <a href="{{ route('app.cases.tab.documents', $case) }}" class="cases-btn-primary">Upload documents first →</a>
                        </p>
                    @endif
                </div>
            @else
                @foreach($messages as $msg)
                    @include('app.cases.tabs._brainstorm_turn', ['msg' => $msg])
                @endforeach
            @endif
        </div>

        {{-- Composer --}}
        @if($isApproved)
            <form class="bs-composer" id="bs-composer" autocomplete="off">
                @csrf
                <div class="bs-composer-inner">
                    <textarea
                        id="bs-input"
                        name="message"
                        rows="1"
                        placeholder="{{ $placeholder }}"
                        maxlength="4000"
                        {{ $hasDocs ? '' : 'disabled' }}
                    ></textarea>
                    <button type="button" class="bs-mic-btn" id="bs-mic-btn" {{ $hasDocs ? '' : 'disabled' }} aria-label="Dictate (English)" title="Hold or click to dictate (English)">
                        {{-- Lucide-style mic: rounded capsule + arch + stand --}}
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="9" y="2" width="6" height="12" rx="3"/>
                            <path d="M5 11a7 7 0 0 0 14 0"/>
                            <line x1="12" y1="18" x2="12" y2="22"/>
                            <line x1="8" y1="22" x2="16" y2="22"/>
                        </svg>
                    </button>
                    <button type="submit" class="bs-send-btn" id="bs-send-btn" {{ $hasDocs ? '' : 'disabled' }} aria-label="Send message">
                        {{-- Crisper paper-plane: filled triangle + crease line --}}
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 2 L2 9.5 L10.5 13.5 L14.5 22 Z" fill="color-mix(in srgb, currentColor 18%, transparent)"/>
                            <path d="M22 2 L10.5 13.5"/>
                        </svg>
                    </button>
                </div>
                <p class="bs-composer-hint">
                    <kbd>⌘</kbd><kbd>↵</kbd> to send · <span id="bs-doc-summary">all docs in scope</span>
                    ·
                    <span class="bs-lang-picker">
                        <label for="bs-model">Model:</label>
                        <select id="bs-model" name="model_override">
                            <option value="" selected>Auto (default)</option>
                            <option value="gemini-flash-lite">Gemini Flash Lite</option>
                            <option value="gemini-flash">Gemini Flash</option>
                            <option value="deepseek-flash">DeepSeek Flash</option>
                            <option value="deepseek-pro">DeepSeek Pro</option>
                        </select>
                    </span>
                    ·
                    <span class="bs-lang-picker">
                        <label for="bs-lang">Reply in:</label>
                        <select id="bs-lang" name="output_language">
                            @foreach($langOptions as $opt)
                                <option value="{{ $opt['value'] }}" {{ $opt['value'] === $defaultLang ? 'selected' : '' }}>
                                    {{ $opt['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </span>
                </p>
            </form>
        @else
            <div class="bs-composer-locked">
                Account pending approval — chat unlocks once admin approves.
            </div>
        @endif
    </main>
</div>

<style>
    .bs-shell {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        /* Lock to remaining viewport so composer is always visible */
        height: calc(100vh - 186px);
        max-height: calc(100vh - 186px);
        min-height: 420px;
        overflow: hidden;
    }
    @media (min-width: 900px) {
        .bs-shell { grid-template-columns: 296px 1fr; gap: 1.25rem; }
    }

    /* ── Left pane ── */
    .bs-context {
        display: flex; flex-direction: column; gap: 0.875rem;
        overflow-y: auto; padding-right: 0.25rem;
        min-height: 0;
    }

    /* Soft cards with accent left-bar */
    .bs-card {
        position: relative;
        display: flex; flex-direction: column; gap: 0.625rem;
        padding: 0.875rem 0.875rem 0.875rem 1rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        transition: border-color 200ms ease-out;
    }
    .bs-card::before {
        content: '';
        position: absolute;
        top: 0.875rem; bottom: 0.875rem; left: 0;
        width: 3px;
        border-radius: 0 3px 3px 0;
    }
    .bs-card-persona::before { background: linear-gradient(180deg, var(--accent) 0%, color-mix(in srgb, var(--accent) 50%, var(--primary)) 100%); }
    .bs-card-docs::before    { background: color-mix(in srgb, var(--accent) 70%, transparent); }
    .bs-card-session::before { background: color-mix(in srgb, var(--link, var(--accent)) 60%, transparent); }
    .bs-card-actions::before { background: color-mix(in srgb, var(--success, var(--accent)) 60%, transparent); }
    .bs-card-tips::before    { background: color-mix(in srgb, var(--warning, var(--accent)) 60%, transparent); }
    .bs-card-privacy::before { background: color-mix(in srgb, var(--success, #16a34a) 60%, transparent); }
    .bs-card:hover { border-color: color-mix(in srgb, var(--accent) 18%, var(--border)); }

    /* ── Persona card ── */
    .bs-card-persona {
        background: linear-gradient(
            135deg,
            color-mix(in srgb, var(--accent) 8%, var(--surface)) 0%,
            var(--surface) 100%
        );
    }
    .bs-persona-head {
        display: flex; align-items: center; gap: 0.75rem;
    }
    .bs-persona-mark {
        display: inline-flex; align-items: center; justify-content: center;
        width: 38px; height: 38px;
        color: var(--accent);
        background: color-mix(in srgb, var(--accent) 12%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--accent) 28%, var(--border));
        border-radius: 10px;
        flex-shrink: 0;
        box-shadow: 0 2px 6px -2px color-mix(in srgb, var(--accent) 25%, transparent);
    }
    .bs-persona-mark svg { width: 20px; height: 20px; }
    .bs-persona-meta { min-width: 0; flex: 1; }
    .bs-persona-name {
        font-family: var(--font-serif, var(--font-sans));
        font-size: 1.0625rem;
        font-weight: 600;
        color: var(--fg);
        line-height: 1.2;
        letter-spacing: -0.005em;
    }
    .bs-persona-tagline {
        font-size: 0.75rem;
        color: var(--fg-muted);
        margin-top: 0.125rem;
        line-height: 1.35;
    }
    .bs-persona-tone {
        margin: 0;
        padding: 0.5rem 0.625rem;
        font-size: 0.8125rem; line-height: 1.5;
        color: var(--fg-muted);
        background: color-mix(in srgb, var(--accent) 4%, transparent);
        border-left: 2px solid color-mix(in srgb, var(--accent) 35%, transparent);
        border-radius: 0 6px 6px 0;
        font-style: italic;
    }

    /* ── Tips card ── */
    .bs-tip-list {
        list-style: none; padding: 0; margin: 0;
        display: flex; flex-direction: column; gap: 0.5rem;
    }
    .bs-tip-list li {
        display: flex; align-items: flex-start; gap: 0.625rem;
        font-size: 0.8125rem; line-height: 1.5;
        color: var(--fg-muted);
    }
    .bs-tip-bullet {
        flex-shrink: 0;
        margin-top: 0.4375rem;
        width: 5px; height: 5px;
        background: var(--accent);
        border-radius: 50%;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 12%, transparent);
    }

    /* ── Privacy card ── */
    .bs-privacy-list {
        list-style: none; padding: 0; margin: 0;
        display: flex; flex-direction: column; gap: 0.4375rem;
    }
    .bs-privacy-list li {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.8125rem; line-height: 1.4;
        color: var(--fg-muted);
    }
    .bs-privacy-dot {
        flex-shrink: 0;
        width: 7px; height: 7px;
        border-radius: 50%;
        background: var(--success, #16a34a);
        box-shadow: 0 0 0 2.5px color-mix(in srgb, var(--success, #16a34a) 18%, transparent);
    }

    .bs-card-count {
        margin-left: auto;
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 1.25rem; height: 1.25rem;
        padding: 0 0.4375rem;
        font-size: 0.6875rem; font-weight: 700;
        font-variant-numeric: tabular-nums;
        background: color-mix(in srgb, var(--accent) 18%, transparent);
        color: var(--fg);
        border-radius: 999px;
        text-transform: none; letter-spacing: 0;
    }

    .bs-section { display: flex; flex-direction: column; gap: 0.625rem; }
    .bs-section-title {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.06em;
        font-weight: 700; color: var(--fg); margin: 0;
    }
    .bs-section-title svg {
        width: 15px; height: 15px;
        color: var(--accent);
        flex-shrink: 0;
    }

    /* Card empty-state — for "No documents uploaded yet" */
    .bs-card-empty {
        display: flex; flex-direction: column; align-items: center;
        text-align: center;
        padding: 0.5rem 0.25rem 0.25rem;
        gap: 0.5rem;
    }
    .bs-card-empty-icon {
        width: 48px; height: 48px;
        color: color-mix(in srgb, var(--accent) 60%, var(--fg-muted));
        opacity: 0.85;
    }
    .bs-card-empty-text {
        font-size: 0.875rem; color: var(--fg-muted); margin: 0;
        line-height: 1.5;
    }
    .bs-card-empty-cta {
        display: inline-flex; align-items: center; gap: 0.375rem;
        margin-top: 0.25rem;
        padding: 0.4375rem 0.75rem;
        font-size: 0.8125rem; font-weight: 600;
        color: var(--accent);
        background: color-mix(in srgb, var(--accent) 8%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 25%, var(--border));
        border-radius: 7px;
        text-decoration: none;
        transition: all 160ms ease-out;
    }
    .bs-card-empty-cta:hover {
        background: color-mix(in srgb, var(--accent) 14%, transparent);
        border-color: color-mix(in srgb, var(--accent) 45%, var(--border));
        transform: translateY(-1px);
    }

    /* Session stats block — three rows of label + value */
    .bs-session-stats {
        display: flex; flex-direction: column;
        gap: 0.4375rem;
    }
    .bs-stat {
        display: flex; justify-content: space-between; align-items: center;
        gap: 0.75rem;
        font-size: 0.875rem;
    }
    .bs-stat-label {
        color: var(--fg-muted);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }
    .bs-stat-value {
        color: var(--fg); font-weight: 500;
        font-variant-numeric: tabular-nums;
    }
    .bs-tier-chip {
        display: inline-flex; align-items: center;
        padding: 0.125rem 0.5625rem;
        font-size: 0.6875rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.05em;
        border-radius: 999px;
    }
    .bs-tier-success {
        background: color-mix(in srgb, var(--success, #16a34a) 14%, transparent);
        color: var(--success, #16a34a);
    }
    .bs-tier-accent {
        background: color-mix(in srgb, var(--accent) 16%, transparent);
        color: var(--accent);
    }
    .bs-tier-fg-muted {
        background: color-mix(in srgb, var(--fg-muted) 12%, transparent);
        color: var(--fg-muted);
    }
    /* Legacy — kept in case any sub-view still uses these */
    .bs-section-empty { font-size: 0.9375rem; color: var(--fg-muted); margin: 0; line-height: 1.6; }
    .bs-section-empty a { color: var(--link); }
    .bs-meta-line { font-size: 0.9375rem; color: var(--fg-muted); margin: 0; line-height: 1.65; }

    .bs-doc-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.3125rem; }
    .bs-doc-row {
        display: flex; align-items: flex-start; gap: 0.5rem;
        padding: 0.4375rem 0.5625rem;
        background: color-mix(in srgb, var(--bg) 30%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--border) 80%, transparent);
        border-radius: 7px; cursor: pointer;
        transition: border-color 150ms ease-out, background 150ms ease-out;
    }
    .bs-doc-row:hover { border-color: color-mix(in srgb, var(--accent) 35%, var(--border)); }
    .bs-doc-row:has(.bs-doc-check:checked) {
        background: color-mix(in srgb, var(--accent) 7%, var(--surface));
        border-color: color-mix(in srgb, var(--accent) 40%, var(--border));
    }
    .bs-doc-check { margin-top: 0.125rem; accent-color: var(--accent); flex-shrink: 0; cursor: pointer; }
    .bs-doc-meta { min-width: 0; flex: 1; }
    .bs-doc-name {
        font-size: 0.875rem; font-weight: 500; color: var(--fg);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .bs-doc-type {
        font-size: 0.75rem; color: var(--fg-muted); margin-top: 0.125rem;
        text-transform: uppercase; letter-spacing: 0.04em;
    }
    .bs-doc-count { font-size: 0.8125rem; color: var(--fg-muted); margin: 0.375rem 0 0; font-variant-numeric: tabular-nums; }

    .bs-quick-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.25rem; }
    .bs-quick-btn {
        display: flex; align-items: center; gap: 0.625rem;
        width: 100%; text-align: left;
        padding: 0.625rem 0.8125rem;
        font-size: 0.875rem; font-weight: 500;
        line-height: 1.35;
        color: var(--fg); background: transparent;
        border: 1px solid var(--border); border-radius: 8px;
        cursor: pointer; font-family: var(--font-sans);
        transition: background 150ms ease-out, border-color 150ms ease-out;
    }
    .bs-quick-btn:hover {
        background: color-mix(in srgb, var(--accent) 5%, var(--surface));
        border-color: color-mix(in srgb, var(--accent) 35%, var(--border));
    }
    .bs-quick-icon { font-size: 0.875rem; flex-shrink: 0; }

    /* ── Right pane ── */
    .bs-chat {
        display: flex; flex-direction: column;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 14px; overflow: hidden;
        min-height: 0;              /* let flex parent constrain us */
        height: 100%;
    }
    .bs-thread {
        flex: 1 1 auto; min-height: 0;
        overflow-y: auto; padding: 1.5rem 1.5rem 0.75rem;
        scroll-behavior: smooth;
    }
    .bs-composer { flex-shrink: 0; }   /* pin composer at bottom of chat column */

    .bs-empty {
        position: relative;
        display: flex; flex-direction: column; align-items: center;
        text-align: center; padding: 2rem 1rem;
        max-width: 480px; margin: auto;
        isolation: isolate;
    }
    /* Soft sigil watermark — sits behind everything, never interactive */
    .bs-empty-watermark {
        position: absolute;
        top: 50%; left: 50%;
        width: 300px; height: 300px;
        transform: translate(-50%, -50%);
        color: var(--accent);
        opacity: 0.04;
        z-index: -1;
        pointer-events: none;
    }
    .bs-empty-mark {
        position: relative;
        display: inline-flex; align-items: center; justify-content: center;
        width: 64px; height: 64px;
        color: var(--accent);
        background:
            radial-gradient(
                circle at 30% 30%,
                color-mix(in srgb, var(--accent) 22%, transparent) 0%,
                color-mix(in srgb, var(--accent) 12%, transparent) 60%,
                transparent 100%
            ),
            color-mix(in srgb, var(--accent) 8%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--accent) 25%, var(--border));
        border-radius: 18px;
        margin-bottom: 1rem;
        box-shadow:
            0 6px 18px -6px color-mix(in srgb, var(--accent) 25%, transparent),
            inset 0 1px 0 color-mix(in srgb, #fff 12%, transparent);
    }
    .bs-empty-mark svg { width: 36px; height: 36px; }
    /* Subtle gold ring around the crest */
    .bs-empty-mark::after {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 28px;
        border: 1px solid color-mix(in srgb, var(--accent) 18%, transparent);
        pointer-events: none;
    }
    .bs-empty-title {
        font-family: var(--font-serif); font-size: 1.4375rem; font-weight: 500;
        color: var(--fg); letter-spacing: -0.012em; margin: 0 0 0.625rem;
    }
    .bs-empty-sub { font-size: 0.9375rem; color: var(--fg-muted); margin: 0; line-height: 1.6; }
    .bs-empty-hint {
        font-size: 0.8125rem; color: var(--fg-muted);
        text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;
        margin: 1.5rem 0 0.625rem;
    }
    .bs-empty-prompts { display: flex; flex-direction: column; gap: 0.4375rem; width: 100%; max-width: 400px; }
    .bs-empty-prompt-card { font-size: 0.875rem !important; padding: 0.75rem 1rem !important; border-radius: 9px !important; }

    .bs-turn { margin-bottom: 1.5rem; }
    .bs-turn-head {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.75rem; color: var(--fg-muted);
        text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .bs-turn-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 20px; height: 20px;
        font-size: 0.75rem; font-weight: 700; border-radius: 50%;
    }
    .bs-turn.user .bs-turn-icon { background: color-mix(in srgb, var(--link) 18%, transparent); color: var(--link); }
    .bs-turn.assistant .bs-turn-icon { background: color-mix(in srgb, var(--accent) 22%, transparent); color: var(--accent); }
    .bs-turn-body {
        padding: 1rem 1.25rem; border-radius: 12px;
        line-height: 1.65; color: var(--fg); font-size: 0.9375rem;
    }
    .bs-turn.user .bs-turn-body {
        background: color-mix(in srgb, var(--link) 8%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--link) 14%, var(--border));
        white-space: pre-wrap;
    }
    .bs-turn.assistant .bs-turn-body {
        background: color-mix(in srgb, var(--accent) 4%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--accent) 18%, var(--border));
    }
    .bs-turn-body p { margin: 0 0 0.625rem; }
    .bs-turn-body p:last-child { margin-bottom: 0; }
    .bs-turn-body ul, .bs-turn-body ol { padding-left: 1.25rem; margin: 0 0 0.625rem; }
    .bs-turn-body li { margin-bottom: 0.25rem; }
    .bs-turn-body strong { color: var(--fg); font-weight: 600; }
    .bs-turn-body em { font-style: italic; }
    .bs-turn-body code {
        background: color-mix(in srgb, var(--fg) 6%, transparent);
        padding: 0.0625rem 0.35rem; border-radius: 4px;
        font-family: var(--font-mono, ui-monospace), monospace; font-size: 0.85em;
    }
    .bs-turn-body h2, .bs-turn-body h3 {
        font-family: var(--font-serif); font-size: 1rem; font-weight: 500;
        margin: 1rem 0 0.5rem;
    }

    /* Bilingual side-by-side render */
    .bs-bilingual {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    @media (min-width: 720px) {
        .bs-bilingual { grid-template-columns: 1fr 1fr; gap: 1rem; }
    }
    .bs-bilingual-col {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.875rem 1rem;
        min-width: 0;
    }
    .bs-bilingual-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--accent);
        margin-bottom: 0.5rem;
        padding-bottom: 0.4rem;
        border-bottom: 1px solid color-mix(in srgb, var(--accent) 25%, var(--border));
    }

    .bs-turn-foot {
        display: flex; align-items: center; gap: 0.625rem;
        font-size: 0.75rem; color: var(--fg-muted);
        margin-top: 0.5rem; padding-left: 0.25rem;
        font-variant-numeric: tabular-nums;
        flex-wrap: wrap;
    }
    .bs-turn-foot-pill {
        display: inline-flex; align-items: center; gap: 0.25rem;
        padding: 0.125rem 0.5625rem; border-radius: 999px;
        background: color-mix(in srgb, var(--success) 10%, transparent);
        color: var(--success); font-weight: 600;
        font-size: 0.6875rem; letter-spacing: 0.04em;
    }
    .bs-turn-foot-pill-paid {
        background: color-mix(in srgb, var(--accent) 14%, transparent);
        color: var(--accent);
    }
    .bs-turn-actions {
        display: inline-flex;
        gap: 0.375rem;
        margin-left: auto;
    }
    .bs-turn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.625rem;
        font-size: 0.75rem;
        font-weight: 500;
        font-family: var(--font-sans);
        color: var(--fg-muted);
        background: transparent;
        border: 1px solid var(--border);
        border-radius: 6px;
        cursor: pointer;
        transition: all 150ms ease-out;
    }
    .bs-turn-action:hover {
        color: var(--fg);
        border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
        background: color-mix(in srgb, var(--accent) 6%, transparent);
    }
    .bs-turn-action.is-copied {
        color: var(--success);
        border-color: color-mix(in srgb, var(--success) 40%, var(--border));
    }

    /* Admin prompt inspector */
    .bs-prompt-debug {
        margin-top: 0.625rem;
        border: 1px dashed color-mix(in srgb, var(--accent) 35%, var(--border));
        border-radius: 8px;
        overflow: hidden;
        font-size: 0.75rem;
    }
    .bs-prompt-debug > summary {
        cursor: pointer;
        padding: 0.375rem 0.75rem;
        color: var(--fg-muted);
        background: color-mix(in srgb, var(--accent) 6%, var(--surface));
        user-select: none;
        list-style: none;
    }
    .bs-prompt-debug > summary::-webkit-details-marker { display: none; }
    .bs-prompt-debug[open] > summary { border-bottom: 1px dashed color-mix(in srgb, var(--accent) 25%, var(--border)); }
    .bs-prompt-debug-section { padding: 0.625rem 0.75rem; }
    .bs-prompt-debug-section + .bs-prompt-debug-section {
        border-top: 1px dashed color-mix(in srgb, var(--accent) 18%, var(--border));
    }
    .bs-prompt-debug-label {
        font-size: 0.6rem; font-weight: 700; letter-spacing: 0.1em;
        text-transform: uppercase; color: var(--fg-muted); margin-bottom: 0.375rem;
    }
    .bs-prompt-debug-pre {
        white-space: pre-wrap; word-break: break-word;
        font-family: monospace; font-size: 0.7rem;
        color: var(--fg); max-height: 24rem; overflow-y: auto;
        background: var(--bg); padding: 0.5rem; border-radius: 4px;
        border: 1px solid var(--border);
    }

    .bs-thinking {
        display: flex; align-items: center; gap: 0.625rem;
        padding: 1rem 1.25rem;
        background: color-mix(in srgb, var(--accent) 4%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--accent) 18%, var(--border));
        border-radius: 12px; color: var(--fg-muted); font-size: 0.9375rem;
    }
    .bs-thinking-dot {
        width: 6px; height: 6px;
        background: var(--accent); border-radius: 50%;
        animation: bs-pulse 1.2s ease-in-out infinite;
    }
    .bs-thinking-dot:nth-child(2) { animation-delay: 0.15s; }
    .bs-thinking-dot:nth-child(3) { animation-delay: 0.3s; }
    @keyframes bs-pulse {
        0%, 100% { opacity: 0.3; transform: scale(0.85); }
        50% { opacity: 1; transform: scale(1); }
    }

    .bs-composer {
        position: relative;
        border-top: 1px solid color-mix(in srgb, var(--accent) 18%, var(--border));
        background:
            linear-gradient(
                180deg,
                color-mix(in srgb, var(--accent) 4%, var(--surface)) 0%,
                var(--surface) 100%
            );
        padding: 0.625rem 0.75rem 0.625rem;
    }
    /* Faint gold hairline glow at the top edge — premium chamber feel */
    .bs-composer::before {
        content: '';
        position: absolute;
        top: -1px; left: 1.5rem; right: 1.5rem;
        height: 1px;
        background: linear-gradient(
            90deg,
            transparent 0%,
            color-mix(in srgb, var(--accent) 50%, transparent) 50%,
            transparent 100%
        );
        pointer-events: none;
    }
    .bs-composer-inner {
        display: flex; gap: 0.5rem; align-items: flex-end;
        background: var(--surface);
        border: 1.5px solid color-mix(in srgb, var(--accent) 12%, var(--border));
        border-radius: 11px;
        padding: 0.25rem 0.375rem 0.25rem 0.75rem;
        box-shadow:
            0 1px 2px color-mix(in srgb, var(--fg) 4%, transparent),
            inset 0 1px 0 color-mix(in srgb, var(--bg) 40%, transparent);
        transition: border-color 180ms ease-out, box-shadow 220ms ease-out;
    }
    .bs-composer-inner:hover {
        border-color: color-mix(in srgb, var(--accent) 25%, var(--border));
    }
    .bs-composer-inner:focus-within {
        border-color: var(--accent);
        box-shadow:
            0 0 0 3px color-mix(in srgb, var(--accent) 14%, transparent),
            0 1px 2px color-mix(in srgb, var(--fg) 4%, transparent);
    }
    #bs-input {
        flex: 1; background: transparent; border: none; outline: none; resize: none;
        font-family: var(--font-sans); font-size: 0.9375rem; line-height: 1.45;
        color: var(--fg); max-height: 140px; padding: 0.3125rem 0;
    }
    #bs-input::placeholder { color: color-mix(in srgb, var(--fg-muted) 70%, transparent); }
    #bs-input:disabled { cursor: not-allowed; opacity: 0.5; }
    .bs-mic-btn, .bs-send-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px;
        border: none; border-radius: 8px;
        cursor: pointer; flex-shrink: 0;
        transition: transform 120ms ease-out, opacity 150ms ease-out,
                    background 180ms ease-out, box-shadow 220ms ease-out,
                    border-color 180ms ease-out;
    }
    .bs-mic-btn {
        background: transparent;
        color: var(--fg-muted);
        border: 1px solid color-mix(in srgb, var(--accent) 15%, var(--border));
    }
    .bs-mic-btn:hover:not(:disabled) {
        color: var(--accent);
        background: color-mix(in srgb, var(--accent) 8%, transparent);
        border-color: color-mix(in srgb, var(--accent) 35%, var(--border));
        transform: translateY(-1px);
    }
    .bs-mic-btn.is-listening {
        background: color-mix(in srgb, var(--danger) 14%, transparent);
        color: var(--danger);
        border-color: color-mix(in srgb, var(--danger) 35%, var(--border));
        animation: bs-mic-pulse 1.5s ease-in-out infinite;
    }
    @keyframes bs-mic-pulse {
        0%, 100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--danger) 25%, transparent); }
        50%      { box-shadow: 0 0 0 6px color-mix(in srgb, var(--danger) 0%, transparent); }
    }
    .bs-mic-btn[hidden] { display: none !important; }
    .bs-send-btn {
        background: linear-gradient(
            135deg,
            var(--primary) 0%,
            color-mix(in srgb, var(--accent) 25%, var(--primary)) 100%
        );
        color: var(--primary-fg);
        box-shadow:
            0 1px 2px color-mix(in srgb, var(--primary) 30%, transparent),
            inset 0 1px 0 color-mix(in srgb, #fff 18%, transparent);
    }
    .bs-send-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow:
            0 6px 14px -4px color-mix(in srgb, var(--accent) 55%, transparent),
            0 2px 4px color-mix(in srgb, var(--primary) 35%, transparent),
            inset 0 1px 0 color-mix(in srgb, #fff 22%, transparent);
    }
    .bs-send-btn:active:not(:disabled) { transform: translateY(0); }
    .bs-send-btn:disabled, .bs-mic-btn:disabled { opacity: 0.4; cursor: not-allowed; box-shadow: none; }
    .bs-composer-hint {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
        font-size: 0.75rem; color: var(--fg-muted);
        margin: 0.5rem 0 0; padding-left: 0.25rem; line-height: 1.4;
    }
    .bs-composer-hint kbd {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 1.25rem; padding: 0.0625rem 0.375rem;
        font-family: var(--font-mono, ui-monospace), monospace; font-size: 0.6875rem;
        font-weight: 600;
        color: var(--fg);
        background: linear-gradient(
            180deg,
            var(--surface) 0%,
            color-mix(in srgb, var(--fg) 4%, var(--surface)) 100%
        );
        border: 1px solid color-mix(in srgb, var(--fg) 12%, var(--border));
        border-bottom-width: 2px;
        border-radius: 4px;
        margin: 0 0.0625rem;
        box-shadow: 0 1px 0 color-mix(in srgb, var(--fg) 6%, transparent);
    }
    .bs-lang-picker {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }
    .bs-lang-picker label { font-weight: 600; }
    .bs-lang-picker select {
        font-size: 0.8125rem;
        font-family: var(--font-sans);
        color: var(--fg);
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 0.1875rem 0.5rem;
        cursor: pointer;
    }
    .bs-lang-picker select:focus { outline: 2px solid var(--accent); outline-offset: 2px; }
    .bs-composer-locked {
        padding: 1.125rem; text-align: center;
        font-size: 0.9375rem; color: var(--fg-muted);
        border-top: 1px solid var(--border);
        background: color-mix(in srgb, var(--warning) 4%, var(--surface));
    }

    @media (prefers-reduced-motion: reduce) {
        .bs-thinking-dot { animation: none; }
        .bs-send-btn, .bs-quick-btn, .bs-doc-row { transition: none; }
    }
</style>

<script>
(function () {
    const composer = document.getElementById('bs-composer');
    if (! composer) return;

    const input = document.getElementById('bs-input');
    const sendBtn = document.getElementById('bs-send-btn');
    const thread = document.getElementById('bs-thread');
    const docCount = document.getElementById('bs-doc-count');
    const docSummary = document.getElementById('bs-doc-summary');
    const msgCountEl = document.getElementById('bs-msg-count');
    const sendUrl = @json($sendUrl);
    const kind = @json($conversation->kind ?? 'brainstorm');
    const isAdmin = @json(auth()->user()->isAdmin());
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || composer.querySelector('input[name="_token"]')?.value
        || '';

    function autoResize() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 200) + 'px';
    }
    input?.addEventListener('input', autoResize);

    function updateDocSummary() {
        const all = document.querySelectorAll('.bs-doc-check');
        const checked = document.querySelectorAll('.bs-doc-check:checked');
        if (docCount) docCount.textContent = `${checked.length} of ${all.length} in scope`;
        if (docSummary) {
            if (checked.length === all.length) docSummary.textContent = 'all docs in scope';
            else if (checked.length === 0) docSummary.textContent = 'no docs in scope';
            else docSummary.textContent = `${checked.length} of ${all.length} docs in scope`;
        }
    }
    document.querySelectorAll('.bs-doc-check').forEach(el => el.addEventListener('change', updateDocSummary));
    updateDocSummary();

    document.querySelectorAll('.bs-quick-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const prompt = btn.getAttribute('data-prompt') || '';
            input.value = prompt;
            input.focus();
            autoResize();
        });
    });

    input?.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            composer.requestSubmit();
        }
    });

    // ── Voice input (browser-native, English only) ──────────────────
    // Webkit SpeechRecognition: Chrome/Edge/Safari support. Firefox doesn't.
    // We hide the mic button entirely if the browser doesn't support it
    // rather than show a button that no-ops mysteriously.
    const micBtn = document.getElementById('bs-mic-btn');
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (! SR) {
        if (micBtn) micBtn.hidden = true;
    } else if (micBtn) {
        let recognition = null;
        let listening = false;

        function startMic() {
            if (listening) return;
            recognition = new SR();
            recognition.lang = 'en-IN';   // Indian-English locale (better for Indian accents)
            recognition.interimResults = true;
            recognition.continuous = true;
            const baseText = (input.value || '').trim();
            let appendedFinal = '';

            recognition.onresult = (event) => {
                let interim = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const res = event.results[i];
                    if (res.isFinal) {
                        appendedFinal += (appendedFinal ? ' ' : '') + res[0].transcript.trim();
                    } else {
                        interim += res[0].transcript;
                    }
                }
                const merged = [baseText, appendedFinal, interim].filter(Boolean).join(' ');
                input.value = merged;
                autoResize();
            };
            recognition.onerror = (e) => {
                console.warn('Voice input error:', e.error);
                stopMic();
            };
            recognition.onend = () => {
                if (listening) {
                    // If the browser auto-stopped (silence), restart for continuous
                    try { recognition.start(); } catch (_) { stopMic(); }
                }
            };

            try {
                recognition.start();
                listening = true;
                micBtn.classList.add('is-listening');
                micBtn.setAttribute('aria-label', 'Stop dictating');
                micBtn.title = 'Click to stop dictating';
            } catch (e) {
                console.warn('Could not start voice input:', e);
            }
        }

        function stopMic() {
            listening = false;
            try { recognition?.stop(); } catch (_) {}
            recognition = null;
            micBtn.classList.remove('is-listening');
            micBtn.setAttribute('aria-label', 'Dictate (English)');
            micBtn.title = 'Click to dictate (English, Indian-English locale)';
            input.focus();
        }

        micBtn.addEventListener('click', () => listening ? stopMic() : startMic());

        // Stop dictating when user submits or clicks Send
        composer.addEventListener('submit', () => { if (listening) stopMic(); });
    }

    /**
     * SAFE markdown-HTML insertion: parse the server-rendered HTML in a
     * detached document, walk every node, strip <script> / <style> /
     * <iframe> / on* attributes / javascript: hrefs. Defense-in-depth:
     * server already escapes via Str::markdown(['html_input' => 'escape']);
     * this is the second line.
     */
    function appendSafeHtml(target, html) {
        const tpl = document.createElement('template');
        tpl.innerHTML = String(html || '');
        const allowedTags = new Set(['p','br','strong','em','code','pre','ul','ol','li',
            'h1','h2','h3','h4','h5','h6','blockquote','a','span','div','table','thead',
            'tbody','tr','td','th','hr']);

        function clean(node) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                const tag = node.tagName.toLowerCase();
                if (! allowedTags.has(tag)) {
                    // Replace with text content of children
                    const textOnly = document.createTextNode(node.textContent || '');
                    node.replaceWith(textOnly);
                    return;
                }
                // Strip dangerous attributes
                Array.from(node.attributes).forEach(attr => {
                    const name = attr.name.toLowerCase();
                    if (name.startsWith('on')) node.removeAttribute(attr.name);
                    if (name === 'href' && /^\s*javascript:/i.test(attr.value)) node.removeAttribute(attr.name);
                    if (name === 'src')  node.removeAttribute(attr.name);
                    if (name === 'style') node.removeAttribute(attr.name);
                });
                Array.from(node.childNodes).forEach(clean);
            }
        }
        Array.from(tpl.content.childNodes).forEach(clean);
        target.appendChild(tpl.content);
    }

    function renderUserTurn(msg) {
        const turn = document.createElement('div');
        turn.className = 'bs-turn user';
        const head = document.createElement('div');
        head.className = 'bs-turn-head';
        const icon = document.createElement('span');
        icon.className = 'bs-turn-icon';
        icon.textContent = 'Y';
        const label = document.createElement('span');
        label.textContent = 'You · just now';
        head.append(icon, label);
        const body = document.createElement('div');
        body.className = 'bs-turn-body';
        body.textContent = msg.content;
        turn.append(head, body);
        return turn;
    }

    function renderAssistantTurn(msg) {
        const turn = document.createElement('div');
        turn.className = 'bs-turn assistant';
        const head = document.createElement('div');
        head.className = 'bs-turn-head';
        const icon = document.createElement('span');
        icon.className = 'bs-turn-icon';
        icon.textContent = 'A';
        const label = document.createElement('span');
        label.textContent = 'Aarambh · just now';
        head.append(icon, label);
        const body = document.createElement('div');
        body.className = 'bs-turn-body';
        if (msg.content_html) {
            appendSafeHtml(body, msg.content_html);
        } else {
            body.textContent = msg.content || '';
        }
        const foot = document.createElement('div');
        foot.className = 'bs-turn-foot';
        const pill = document.createElement('span');
        const tier = (msg.tier || '').toLowerCase();
        if (tier === 'free') {
            pill.className = 'bs-turn-foot-pill';
            pill.textContent = 'FREE';
        } else {
            pill.className = 'bs-turn-foot-pill bs-turn-foot-pill-paid';
            pill.textContent = 'PAID';
        }
        foot.appendChild(pill);
        const tokens = document.createElement('span');
        const tIn = msg.tokens_in || 0; const tOut = msg.tokens_out || 0;
        tokens.textContent = `${(tIn + tOut).toLocaleString()} tokens`;
        foot.appendChild(tokens);
        if (msg.model_used) {
            const model = document.createElement('span');
            model.textContent = msg.model_used;
            foot.appendChild(model);
        }
        // Action row: Copy + Add to Draft (Add to Draft hidden when we're on the Draft tab itself)
        const actions = document.createElement('span');
        actions.className = 'bs-turn-actions';
        const copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.className = 'bs-turn-action';
        copyBtn.setAttribute('data-copy', String(msg.id));
        copyBtn.title = 'Copy markdown to clipboard';
        copyBtn.appendChild(document.createTextNode(' Copy'));
        actions.appendChild(copyBtn);
        if (kind !== 'draft' && msg.id) {
            const draftBtn = document.createElement('button');
            draftBtn.type = 'submit';
            draftBtn.className = 'bs-turn-action';
            draftBtn.title = 'Send this answer to the Draft tab as a starting message';
            draftBtn.appendChild(document.createTextNode(' Add to Draft'));
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/app/cases/{{ $case->id }}/messages/${msg.id}/copy-to-draft`;
            form.style.display = 'inline';
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = csrfToken;
            form.appendChild(csrf);
            form.appendChild(draftBtn);
            actions.appendChild(form);
        }
        if (kind !== 'notes' && msg.id) {
            const notesBtn = document.createElement('button');
            notesBtn.type = 'submit';
            notesBtn.className = 'bs-turn-action';
            notesBtn.title = 'Save this answer to the Notes tab';
            notesBtn.appendChild(document.createTextNode(' Add to Notes'));
            const notesForm = document.createElement('form');
            notesForm.method = 'POST';
            notesForm.action = `/app/cases/{{ $case->id }}/messages/${msg.id}/copy-to-notes`;
            notesForm.style.display = 'inline';
            const notesCsrf = document.createElement('input');
            notesCsrf.type = 'hidden'; notesCsrf.name = '_token'; notesCsrf.value = csrfToken;
            notesForm.appendChild(notesCsrf);
            notesForm.appendChild(notesBtn);
            actions.appendChild(notesForm);
        }
        foot.appendChild(actions);
        // Hidden source for Copy
        const ta = document.createElement('textarea');
        ta.setAttribute('data-copy-source', String(msg.id));
        ta.hidden = true;
        ta.value = msg.content || '';
        // Admin-only: collapsible prompt inspector
        if (isAdmin && msg.debug_prompts) {
            const details = document.createElement('details');
            details.className = 'bs-prompt-debug';
            const summary = document.createElement('summary');
            summary.textContent = '🔍 View Prompt';
            details.appendChild(summary);
            const sysSection = document.createElement('div');
            sysSection.className = 'bs-prompt-debug-section';
            const sysLabel = document.createElement('div');
            sysLabel.className = 'bs-prompt-debug-label';
            sysLabel.textContent = 'SYSTEM PROMPT';
            const sysPre = document.createElement('pre');
            sysPre.className = 'bs-prompt-debug-pre';
            sysPre.textContent = msg.debug_prompts.system || '';
            sysSection.append(sysLabel, sysPre);
            const userSection = document.createElement('div');
            userSection.className = 'bs-prompt-debug-section';
            const userLabel = document.createElement('div');
            userLabel.className = 'bs-prompt-debug-label';
            userLabel.textContent = 'USER MESSAGE SENT';
            const userPre = document.createElement('pre');
            userPre.className = 'bs-prompt-debug-pre';
            userPre.textContent = msg.debug_prompts.user || '';
            userSection.append(userLabel, userPre);
            details.append(sysSection, userSection);
            turn.append(head, body, foot, ta, details);
        } else {
            turn.append(head, body, foot, ta);
        }
        return turn;
    }

    function renderThinking() {
        const wrap = document.createElement('div');
        wrap.className = 'bs-turn assistant';
        wrap.id = 'bs-thinking';
        const head = document.createElement('div');
        head.className = 'bs-turn-head';
        const icon = document.createElement('span');
        icon.className = 'bs-turn-icon';
        icon.textContent = 'A';
        const label = document.createElement('span');
        label.textContent = 'Aarambh is thinking…';
        head.append(icon, label);
        const body = document.createElement('div');
        body.className = 'bs-thinking';
        for (let i = 0; i < 3; i++) {
            const dot = document.createElement('span');
            dot.className = 'bs-thinking-dot';
            body.appendChild(dot);
        }
        wrap.append(head, body);
        return wrap;
    }

    function clearEmptyState() {
        const empty = thread.querySelector('.bs-empty');
        if (empty) empty.remove();
    }
    function scrollToBottom() {
        thread.scrollTop = thread.scrollHeight;
    }
    function bumpMsgCount(delta) {
        if (msgCountEl) msgCountEl.textContent = (parseInt(msgCountEl.textContent || '0', 10) || 0) + delta;
    }

    composer.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = (input.value || '').trim();
        if (! text) return;

        // Collect currently-checked doc IDs to send with this turn
        const docIds = Array.from(document.querySelectorAll('.bs-doc-check:checked'))
            .map(el => parseInt(el.getAttribute('data-doc-id'), 10))
            .filter(n => Number.isFinite(n));

        clearEmptyState();
        const userTurn = renderUserTurn({ content: text });
        thread.appendChild(userTurn);
        const thinking = renderThinking();
        thread.appendChild(thinking);
        scrollToBottom();

        input.value = '';
        autoResize();
        sendBtn.disabled = true;
        input.disabled = true;

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: text,
                    document_ids: docIds,
                    kind: kind,
                    output_language: (document.getElementById('bs-lang')?.value || null),
                    model_override: (document.getElementById('bs-model')?.value || null),
                }),
            });

            thinking.remove();

            if (! res.ok) {
                const err = await res.json().catch(() => ({}));
                const errEl = document.createElement('div');
                errEl.className = 'bs-turn assistant';
                const errBody = document.createElement('div');
                errBody.className = 'bs-turn-body';
                errBody.style.borderColor = 'var(--danger)';
                errBody.style.background = 'color-mix(in srgb, var(--danger) 6%, var(--surface))';
                errBody.textContent = err.message || 'Something went wrong. Try again.';
                errEl.appendChild(errBody);
                thread.appendChild(errEl);
                scrollToBottom();
                return;
            }

            const data = await res.json();
            userTurn.remove();
            thread.appendChild(renderUserTurn(data.user_message));
            if (data.debug_prompts) { data.assistant_message.debug_prompts = data.debug_prompts; }
            thread.appendChild(renderAssistantTurn(data.assistant_message));
            bumpMsgCount(2);
            scrollToBottom();
        } catch (err) {
            thinking.remove();
            const errEl = document.createElement('div');
            errEl.className = 'bs-turn assistant';
            const errBody = document.createElement('div');
            errBody.className = 'bs-turn-body';
            errBody.style.borderColor = 'var(--danger)';
            errBody.textContent = 'Network error: ' + (err?.message || 'unknown');
            errEl.appendChild(errBody);
            thread.appendChild(errEl);
            scrollToBottom();
        } finally {
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });

    if (thread.querySelector('.bs-turn')) scrollToBottom();

    // Copy-to-clipboard on assistant turns. Uses textareas (data-copy-source)
    // rather than reading rendered text so we get the original markdown.
    thread.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-copy]');
        if (! btn) return;
        const id = btn.getAttribute('data-copy');
        const ta = thread.querySelector(`textarea[data-copy-source="${id}"]`);
        let text;
        if (ta) {
            text = ta.value;
        } else {
            // Fallback for JS-appended turns: grab the visible text
            const body = btn.closest('.bs-turn')?.querySelector('.bs-turn-body');
            text = body ? body.innerText : '';
        }
        try {
            await navigator.clipboard.writeText(text);
            const orig = btn.innerHTML;
            btn.classList.add('is-copied');
            // Replace label only — keep icon as is via DOM safe path
            const label = btn.lastChild;
            if (label && label.nodeType === Node.TEXT_NODE) label.textContent = ' Copied';
            setTimeout(() => {
                btn.classList.remove('is-copied');
                if (label && label.nodeType === Node.TEXT_NODE) label.textContent = ' Copy';
            }, 1500);
        } catch (err) {
            // Clipboard API requires HTTPS / secure context. Should be fine on prod.
        }
    });
})();
</script>
