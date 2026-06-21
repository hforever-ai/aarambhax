<x-layouts.app-shell title="{{ $karya->title }}">
    <section class="container-page py-10">
        <nav aria-label="Breadcrumb" class="mb-4 text-sm flex items-center gap-2" style="color: var(--fg-muted);">
            <a href="{{ route('app.cases.show', $case) }}" class="text-link">← {{ $case->title }}</a>
            <span>/</span>
            <span>Work</span>
        </nav>

        <header class="mb-6 flex items-start justify-between gap-4 flex-wrap">
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1 text-xs uppercase tracking-wider" style="color: var(--fg-muted);">
                    <span class="px-2 py-0.5 rounded font-mono" style="background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent);">{{ $catalogueEntry['label_en'] ?? $karya->type }}</span>
                    @if($karya->language)
                        <span>· {{ strtoupper($karya->language) }}</span>
                    @endif
                </div>
                <h1 class="h1-page">{{ $karya->title }}</h1>
                <p class="mt-2 text-sm" style="color: var(--fg-muted);">
                    {{ count($karya->input_document_ids ?? []) }} {{ \Illuminate\Support\Str::plural('document', count($karya->input_document_ids ?? [])) }} ·
                    {{ $karya->created_at->diffForHumans() }}
                    @if($karya->model_used)
                        · {{ $karya->model_used }}
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('app.cases.karyas.destroy', [$case, $karya]) }}" onsubmit="return confirm('Delete this Work item permanently?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost text-sm" style="color: var(--danger);">Delete</button>
            </form>
        </header>

        @if(session('flash'))
            <div class="card mb-4" style="border-color: var(--success); background: color-mix(in srgb, var(--success) 8%, var(--surface));" role="status">
                <p style="color: var(--success);">{{ session('flash') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main column: progress while running, artifact when done --}}
            <div class="lg:col-span-2">
                {{-- Pipeline progress card (shown when not done) --}}
                <div id="karya-progress-wrap" class="card p-6 {{ $karya->pipeline_status === 'done' ? 'hidden' : '' }}" style="background: var(--surface); border-color: var(--border);">
                    <div class="flex items-center gap-3 mb-4">
                        <div id="karya-spinner" class="w-5 h-5 rounded-full border-2 border-t-transparent animate-spin {{ $karya->pipeline_status === 'failed' ? 'hidden' : '' }}" style="border-color: var(--accent); border-top-color: transparent;"></div>
                        <div id="karya-failed-icon" class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold {{ $karya->pipeline_status === 'failed' ? '' : 'hidden' }}" style="background: var(--danger); color: var(--primary-fg);">!</div>
                        <h2 class="text-lg font-serif font-medium" style="color: var(--fg);">
                            <span id="karya-stage-title">{{ $karya->pipeline_status === 'failed' ? 'Work failed' : 'Running…' }}</span>
                        </h2>
                    </div>

                    <div class="mb-3">
                        <div class="h-2 w-full rounded-full overflow-hidden" style="background: var(--border);">
                            <div id="karya-progress-bar" class="h-full transition-all duration-500 ease-out" style="width: {{ (int) ($karya->pipeline_progress ?? 0) }}%; background: var(--accent);"></div>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-2" style="color: var(--fg-muted);">
                            <span id="karya-stage-text">{{ $karya->pipeline_stage ?? 'queued' }}</span>
                            <span id="karya-progress-pct">{{ (int) ($karya->pipeline_progress ?? 0) }}%</span>
                        </div>
                    </div>

                    <div id="karya-error-block" class="mt-4 text-sm {{ $karya->pipeline_status === 'failed' ? '' : 'hidden' }}" style="color: var(--danger);">
                        <strong>Error:</strong> <span id="karya-error-text">{{ $karya->pipeline_error }}</span>
                    </div>

                    <p class="text-xs mt-4" style="color: var(--fg-muted);">
                        This runs in the background. Safe to leave this page open or come back later — output appears here when ready.
                    </p>
                </div>

                {{-- Final artifact (markdown body) — shown when done --}}
                <article id="karya-artifact" class="card p-8 prose-karya {{ $karya->pipeline_status === 'done' ? '' : 'hidden' }}" style="background: var(--surface); border-color: var(--border);">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b" style="border-color: var(--border);">
                        <span class="text-xs uppercase tracking-wider" style="color: var(--fg-muted);">Output</span>
                        <button type="button" id="karya-copy-btn" class="text-xs px-2 py-1 rounded" style="border: 1px solid var(--border); color: var(--fg-muted);">Copy</button>
                    </div>
                    <div id="karya-output-md" class="karya-md">{!! \Illuminate\Support\Str::of($karya->output_markdown ?? '')->markdown(['html_input' => 'escape']) !!}</div>
                </article>
            </div>

            {{-- Sidebar: documents used + meta --}}
            <aside class="space-y-4">
                <div class="card p-4" style="background: var(--surface); border-color: var(--border);">
                    <h3 class="text-xs uppercase tracking-wider mb-3" style="color: var(--fg-muted);">Documents used</h3>
                    @if($documents->isEmpty())
                        <p class="text-sm" style="color: var(--fg-muted);">No documents resolved.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach($documents as $doc)
                                <li class="text-sm flex items-start gap-2">
                                    <span class="mt-0.5 flex-shrink-0" style="color: var(--accent);">▸</span>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium" style="color: var(--fg);">{{ \Illuminate\Support\Str::limit($doc->original_filename, 40) }}</div>
                                        @if($doc->detected_doc_type)
                                            <div class="text-[10px] uppercase tracking-wider" style="color: var(--fg-muted);">{{ str_replace('_', ' ', $doc->detected_doc_type) }}</div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if($karya->pipeline_status === 'done' && ($karya->tokens_in || $karya->tokens_out))
                    <div class="card p-4" style="background: var(--surface); border-color: var(--border);">
                        <h3 class="text-xs uppercase tracking-wider mb-3" style="color: var(--fg-muted);">Run details</h3>
                        <dl class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <dt style="color: var(--fg-muted);">Tier</dt>
                                <dd>
                                    @if($karya->tier === 'free')
                                        <span style="color: var(--success); font-weight: 500;">FREE</span>
                                    @elseif($karya->tier === 'paid')
                                        <span style="color: var(--accent); font-weight: 500;">PAID</span>
                                    @else
                                        <span style="color: var(--fg-muted);">—</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between"><dt style="color: var(--fg-muted);">Model</dt><dd style="color: var(--fg);">{{ $karya->model_used }}</dd></div>
                            <div class="flex justify-between"><dt style="color: var(--fg-muted);">Tokens in</dt><dd style="color: var(--fg);">{{ number_format($karya->tokens_in) }}</dd></div>
                            <div class="flex justify-between"><dt style="color: var(--fg-muted);">Tokens out</dt><dd style="color: var(--fg);">{{ number_format($karya->tokens_out) }}</dd></div>
                            <div class="flex justify-between">
                                <dt style="color: var(--fg-muted);">Cost</dt>
                                <dd style="color: var(--fg);">
                                    @if($karya->tier === 'free')
                                        <span style="color: var(--success);">₹0.00</span>
                                        <span class="text-xs" style="color: var(--fg-muted);">(saved ₹{{ number_format($karya->paid_equivalent_paise / 100, 2) }})</span>
                                    @else
                                        ₹{{ number_format($karya->cost_inr_paise / 100, 2) }}
                                    @endif
                                </dd>
                            </div>
                            @if($karya->pii_redactions > 0)
                                <div class="flex justify-between">
                                    <dt style="color: var(--fg-muted);">PII redacted</dt>
                                    <dd style="color: var(--fg);">{{ $karya->pii_redactions }} item(s)</dd>
                                </div>
                            @endif
                            @if($karya->pipeline_started_at && $karya->pipeline_finished_at)
                                <div class="flex justify-between"><dt style="color: var(--fg-muted);">Time</dt><dd style="color: var(--fg);">{{ $karya->pipeline_started_at->diffInSeconds($karya->pipeline_finished_at) }}s</dd></div>
                            @endif
                        </dl>
                    </div>
                @endif
            </aside>
        </div>
    </section>

    <style>
        .karya-md h2 { font-family: var(--font-serif); font-size: 1.25rem; font-weight: 500; color: var(--fg); margin-top: 1.75rem; margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid var(--border); }
        .karya-md h2:first-child { margin-top: 0; }
        .karya-md h3 { font-family: var(--font-serif); font-size: 1.05rem; font-weight: 500; color: var(--fg); margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .karya-md p { color: var(--fg); line-height: 1.7; margin-bottom: 0.875rem; }
        .karya-md ul, .karya-md ol { color: var(--fg); padding-left: 1.5rem; margin-bottom: 0.875rem; }
        .karya-md ul { list-style: disc; }
        .karya-md ol { list-style: decimal; }
        .karya-md li { margin-bottom: 0.4rem; line-height: 1.6; }
        .karya-md strong { color: var(--fg); font-weight: 600; }
        .karya-md em { color: var(--fg); font-style: italic; }
        .karya-md code { background: color-mix(in srgb, var(--border) 50%, transparent); padding: 0.1rem 0.35rem; border-radius: 4px; font-family: var(--font-mono); font-size: 0.875em; color: var(--accent); }
        .karya-md table { border-collapse: collapse; width: 100%; margin: 1rem 0; font-size: 0.875rem; }
        .karya-md th, .karya-md td { border: 1px solid var(--border); padding: 0.5rem 0.75rem; text-align: left; color: var(--fg); }
        .karya-md th { background: color-mix(in srgb, var(--accent) 8%, var(--surface)); font-family: var(--font-serif); font-weight: 500; }
        .karya-md blockquote { border-left: 3px solid var(--accent); padding-left: 1rem; margin: 1rem 0; color: var(--fg-muted); font-style: italic; }
    </style>

    @if($karya->pipeline_status !== 'done' && $karya->pipeline_status !== 'failed')
    <script>
    (function () {
        const statusUrl = @json(route('app.cases.karyas.status', [$case, $karya]));
        const showUrl = @json(route('app.cases.karyas.show', [$case, $karya]));
        let intervalMs = 2500;
        let pollTimer = null;

        async function poll() {
            try {
                const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                if (! res.ok) return;
                const data = await res.json();

                document.getElementById('karya-progress-bar').style.width = (data.progress || 0) + '%';
                document.getElementById('karya-progress-pct').textContent = (data.progress || 0) + '%';
                document.getElementById('karya-stage-text').textContent = data.stage || '';

                if (data.status === 'failed') {
                    clearInterval(pollTimer);
                    document.getElementById('karya-stage-title').textContent = 'Work failed';
                    document.getElementById('karya-spinner').classList.add('hidden');
                    document.getElementById('karya-failed-icon').classList.remove('hidden');
                    document.getElementById('karya-error-block').classList.remove('hidden');
                    document.getElementById('karya-error-text').textContent = data.error || 'Unknown error';
                    return;
                }

                if (data.ready) {
                    // Reload page so server-rendered markdown + sidebar refresh together
                    window.location.href = showUrl;
                }
            } catch (e) {
                // soft-fail; next tick will retry
            }
        }

        pollTimer = setInterval(poll, intervalMs);
        poll(); // immediate first poll so progress catches up after the redirect
    })();
    </script>
    @endif

    <script>
    document.getElementById('karya-copy-btn')?.addEventListener('click', () => {
        const md = @json($karya->output_markdown ?? '');
        navigator.clipboard.writeText(md).then(() => {
            const btn = document.getElementById('karya-copy-btn');
            const orig = btn.textContent;
            btn.textContent = 'Copied ✓';
            setTimeout(() => btn.textContent = orig, 1500);
        });
    });
    </script>
</x-layouts.app-shell>
