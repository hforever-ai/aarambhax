<x-layouts.app-shell title="{{ $analysis->title }} — {{ $case->title }}">
    <section class="container-page py-6 max-w-7xl">
        <nav aria-label="Breadcrumb" class="mb-3 text-sm">
            <a href="{{ route('app.cases.show', $case) }}" class="text-link">← {{ $case->title }}</a>
        </nav>

        <header class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h1-page">{{ $analysis->title }}</h1>
                <div class="flex items-center gap-2 mt-2 flex-wrap text-xs">
                    <span class="badge badge-accent">analysis</span>
                    <span class="badge" style="background: color-mix(in srgb, var(--link) 18%, transparent); color: var(--link);">{{ strtoupper($analysis->language) }}</span>
                    <span class="badge" style="background: var(--surface-2);">{{ str_replace('_', ' ', $analysis->analysis_type) }}</span>
                    <span class="text-fg-muted">· Updated {{ $analysis->updated_at->diffForHumans() }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <form method="POST" action="{{ route('app.cases.analyses.convert', [$case, $analysis]) }}"
                      onsubmit="return confirm('Convert this analysis to a draft using your chambers catalogue?');" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Convert to draft →</button>
                </form>
                <form method="POST" action="{{ route('app.cases.analyses.destroy', [$case, $analysis]) }}" onsubmit="return confirm('Delete this analysis?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-secondary text-sm">Delete</button>
                </form>
            </div>
        </header>

        @if(session('edit_success'))
            <div class="card mb-4" style="border-color: var(--success); background: color-mix(in srgb, var(--success) 8%, var(--surface));" role="status">
                <p class="text-success">✓ {{ session('edit_success') }}</p>
            </div>
        @endif

        @php($pipelineRunning = in_array($analysis->pipeline_status, ['queued', 'running'], true))
        @php($pipelineFailed  = $analysis->pipeline_status === 'failed')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Analysis body OR progress panel --}}
            <div class="lg:col-span-8 space-y-4">
                <article class="card" id="analysis-card"
                         data-status-url="{{ route('app.cases.analyses.status', [$case, $analysis]) }}"
                         data-status="{{ $analysis->pipeline_status }}">
                    @if($pipelineRunning)
                        <div id="pipeline-pending" class="text-center py-10">
                            <div class="inline-flex items-center gap-3 mb-4">
                                <span class="inline-block w-3 h-3 rounded-full bg-current text-accent" style="animation: pulse 1.4s infinite;"></span>
                                <h2 class="h2-section font-semibold">Aarambh is working on your analysis</h2>
                            </div>
                            <p class="lead mb-6" id="pipeline-stage">{{ $analysis->pipeline_stage ?: 'queued' }}</p>

                            <div class="mx-auto" style="max-width: 480px;">
                                <div style="background: var(--surface-2); border-radius: 999px; height: 10px; overflow: hidden;" role="progressbar"
                                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $analysis->pipeline_progress }}">
                                    <div id="pipeline-bar" style="background: var(--accent); height: 100%; width: {{ $analysis->pipeline_progress }}%; transition: width 0.4s ease;"></div>
                                </div>
                                <p class="text-xs text-fg-muted mt-2"><span id="pipeline-progress">{{ $analysis->pipeline_progress }}</span>%</p>
                            </div>

                            <p class="text-xs text-fg-muted mt-6">
                                Takes 1-3 minutes for typical case bundles. You can navigate away and return — this page will update.
                            </p>
                        </div>
                    @elseif($pipelineFailed)
                        <div class="text-center py-8">
                            <p class="font-serif text-xl font-semibold text-warning mb-2">Analysis failed</p>
                            <p class="text-sm text-fg-muted mb-3">{{ $analysis->pipeline_error ?: 'Unknown error.' }}</p>
                            <a href="{{ route('app.cases.analyses.create', $case) }}" class="btn btn-primary">Try again</a>
                        </div>
                    @elseif(empty($analysis->current_content_md))
                        <p class="text-fg-muted text-center py-8">Analysis is empty. Generation may have failed.</p>
                    @else
                        <div class="prose-aarambhax text-fg" id="analysis-body">
                            {!! \Illuminate\Support\Str::markdown($analysis->current_content_md) !!}
                        </div>
                    @endif
                </article>
            </div>

            {{-- Chat sidebar --}}
            <aside class="lg:col-span-4">
                <div class="card sticky top-4" style="padding: 0;">
                    <header class="px-4 py-3 border-b border-default">
                        <h2 class="h3-card">💬 Refine analysis</h2>
                        <p class="text-xs mt-1 text-fg-muted">AI keeps full memory of this case + your prior turns.</p>
                    </header>

                    <div class="px-4 py-3 max-h-96 overflow-y-auto space-y-3" id="chat-history">
                        @forelse($analysis->messages as $msg)
                            @if($msg->role === 'user')
                                <div class="flex justify-end">
                                    <div class="rounded-lg px-3 py-2 max-w-xs text-sm" style="background: var(--primary); color: var(--primary-fg);">
                                        @if($msg->intent && $msg->intent !== 'free_form')
                                            <p class="text-xs font-mono opacity-75 mb-1">{{ $msg->intent }}</p>
                                        @endif
                                        {{ $msg->content }}
                                    </div>
                                </div>
                            @elseif($msg->role === 'assistant')
                                <div class="flex justify-start">
                                    <div class="rounded-lg px-3 py-2 max-w-xs text-sm" style="background: var(--surface-2); color: var(--fg);">
                                        <p class="text-xs font-mono mb-1 text-fg-muted">{{ $msg->intent ?? 'assistant' }} · {{ $msg->created_at?->diffForHumans() }}</p>
                                        <p style="white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($msg->content, 250) }}</p>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-center text-sm py-6 text-fg-muted">No refinements yet.</p>
                        @endforelse
                    </div>

                    @error('edit')
                        <div class="px-4 py-2 text-sm text-danger">{{ $message }}</div>
                    @enderror

                    <div class="border-t border-default px-4 py-3">
                        <p class="text-xs uppercase tracking-wider text-fg-muted mb-2">Quick actions</p>
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <form method="POST" action="{{ route('app.cases.analyses.edit', [$case, $analysis]) }}">
                                @csrf <input type="hidden" name="intent" value="tighten">
                                <button type="submit" class="btn btn-secondary text-sm w-full">Tighten</button>
                            </form>
                            <form method="POST" action="{{ route('app.cases.analyses.edit', [$case, $analysis]) }}">
                                @csrf <input type="hidden" name="intent" value="add_risk">
                                <button type="submit" class="btn btn-secondary text-sm w-full">Add risks</button>
                            </form>
                            <form method="POST" action="{{ route('app.cases.analyses.edit', [$case, $analysis]) }}">
                                @csrf <input type="hidden" name="intent" value="suggest_precedent">
                                <button type="submit" class="btn btn-secondary text-sm w-full">Suggest precedent</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('app.cases.analyses.edit', [$case, $analysis]) }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="intent" value="free_form">
                            <textarea name="instruction" rows="2" placeholder="Free-form instruction…" class="input text-sm"></textarea>
                            <button type="submit" class="btn btn-primary text-sm w-full">Send</button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    @if($pipelineRunning)
        <style>
            @keyframes pulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50%      { opacity: 0.4; transform: scale(0.8); }
            }
        </style>
        <script>
            (function () {
                var card = document.getElementById('analysis-card');
                if (!card) return;
                var url = card.getAttribute('data-status-url');
                var bar = document.getElementById('pipeline-bar');
                var pct = document.getElementById('pipeline-progress');
                var stage = document.getElementById('pipeline-stage');

                function tick() {
                    fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            if (bar)   bar.style.width = (d.progress || 0) + '%';
                            if (pct)   pct.textContent = (d.progress || 0);
                            if (stage) stage.textContent = d.stage || d.status;

                            if (d.status === 'done') {
                                window.location.reload();
                            } else if (d.status === 'failed') {
                                window.location.reload();
                            }
                        })
                        .catch(function () { /* swallow; will retry */ });
                }
                tick();
                setInterval(tick, 2500);
            })();
        </script>
    @endif
</x-layouts.app-shell>
