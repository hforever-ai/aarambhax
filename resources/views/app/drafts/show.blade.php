<x-layouts.app-shell :title="$draft->title.' — Aarambhax'">
    <section class="container-page py-6 max-w-7xl">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <a href="{{ route('app.drafts.index') }}" class="text-sm" style="color: var(--fg-muted);">← All drafts</a>
                <h1 class="text-2xl sm:text-3xl font-serif font-medium mt-1" style="color: var(--fg);">{{ $draft->title }}</h1>
                <div class="flex items-center gap-2 mt-2 flex-wrap text-xs">
                    <span class="badge badge-accent">{{ str_replace('_', ' ', $draft->forum) }}</span>
                    <span class="badge" style="background: color-mix(in srgb, var(--link) 18%, transparent); color: var(--link);">{{ strtoupper($draft->language) }}</span>
                    <span class="badge" style="background: var(--surface-2);">{{ $draft->draft_type }}</span>
                    <span style="color: var(--fg-muted);">· Updated {{ $draft->updated_at->diffForHumans() }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('app.drafts.destroy', $draft) }}" onsubmit="return confirm('Delete this draft permanently?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost text-sm" style="color: var(--danger);">Delete</button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- LEFT: draft body + citation panel --}}
            <div class="lg:col-span-8 space-y-4">
                <div class="card">
                    @if($draft->current_content_md === '' || $draft->current_content_md === null)
                        <div class="text-center py-12">
                            <p style="color: var(--fg-muted);">Draft is empty. Generation may have failed.</p>
                            <form method="POST" action="{{ route('app.drafts.edit', $draft) }}" class="mt-4 inline">
                                @csrf
                                <input type="hidden" name="intent" value="free_form">
                                <input type="hidden" name="instruction" value="Generate the initial draft from the case facts">
                                <button type="submit" class="btn btn-primary">Retry generation</button>
                            </form>
                        </div>
                    @else
                        <article class="prose-aarambhax" id="draft-body" data-draft-id="{{ $draft->id }}" style="color: var(--fg);">
                            {!! \Illuminate\Support\Str::markdown($draft->current_content_md) !!}
                        </article>

                        {{-- Inline selection toolbar — appears when text selected --}}
                        <div id="selection-toolbar" role="toolbar" aria-label="Edit selected text" style="position: fixed; display: none; z-index: 100; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 4px 14px -4px rgba(0,0,0,0.18); padding: 0.25rem; gap: 0.25rem; flex-direction: row; align-items: center;">
                            <button type="button" data-intent="tighten" class="btn btn-ghost text-xs" style="padding: 0.25rem 0.625rem; min-height: 28px;">Tighten</button>
                            <button type="button" data-intent="rewrite_section" class="btn btn-ghost text-xs" style="padding: 0.25rem 0.625rem; min-height: 28px;">Rewrite</button>
                            <button type="button" data-intent="add_citation" class="btn btn-ghost text-xs" style="padding: 0.25rem 0.625rem; min-height: 28px;">Add citation</button>
                            <span style="border-left: 1px solid var(--border); height: 16px;"></span>
                            <button type="button" data-intent="free_form" class="btn btn-ghost text-xs" style="padding: 0.25rem 0.625rem; min-height: 28px; color: var(--accent);">Ask AI…</button>
                        </div>

                        {{-- Hidden form posted by toolbar via JS --}}
                        <form id="inline-edit-form" method="POST" action="{{ route('app.drafts.edit', $draft) }}" style="display: none;">
                            @csrf
                            <input type="hidden" name="intent" id="inline-intent">
                            <input type="hidden" name="instruction" id="inline-instruction">
                            <input type="hidden" name="selection_text" id="inline-selection-text">
                            <input type="hidden" name="selection_start" id="inline-selection-start">
                            <input type="hidden" name="selection_end" id="inline-selection-end">
                        </form>
                    @endif
                </div>

                @if($draft->citations->isNotEmpty())
                    <div class="card">
                        <h2 class="font-serif text-lg font-semibold mb-3" style="color: var(--fg);">Citations in this draft</h2>
                        <ul role="list" class="space-y-1.5 text-sm">
                            @foreach($draft->citations->whereNull('removed_in_message_id') as $cit)
                                <li class="flex items-center gap-2">
                                    @if($cit->verification_status === 'verified')
                                        <span class="badge badge-success">✓</span>
                                    @elseif($cit->verification_status === 'suspect')
                                        <span class="badge badge-warning">⚠</span>
                                    @elseif($cit->verification_status === 'hallucinated')
                                        <span class="badge badge-danger">✗</span>
                                    @else
                                        <span class="badge" style="background: var(--surface-2);">…</span>
                                    @endif
                                    <code style="color: var(--fg);">{{ $cit->raw_text }}</code>
                                    <span style="color: var(--fg-muted);">{{ $cit->verification_status }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- RIGHT: chat sidebar --}}
            <aside class="lg:col-span-4">
                <div class="card sticky top-4" style="padding: 0;">
                    <header class="px-4 py-3 border-b" style="border-color: var(--border);">
                        <h2 class="font-serif text-lg font-semibold" style="color: var(--fg);">💬 Edit by chat</h2>
                        <p class="text-xs mt-0.5" style="color: var(--fg-muted);">AI keeps full memory of your case facts, parties, and previous edits.</p>
                    </header>

                    <div class="px-4 py-3 max-h-96 overflow-y-auto space-y-3" id="chat-history">
                        @forelse($draft->messages as $msg)
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
                                        <p class="text-xs font-mono mb-1" style="color: var(--fg-muted);">{{ $msg->intent ?? 'assistant' }} · {{ $msg->created_at->diffForHumans() }}</p>
                                        <p style="white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($msg->content, 200) }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-xs" style="color: var(--fg-muted);">{{ $msg->content }}</div>
                            @endif
                        @empty
                            <p class="text-center text-sm py-6" style="color: var(--fg-muted);">No edits yet. Try a quick action or type below.</p>
                        @endforelse
                    </div>

                    @error('edit')
                        <div class="px-4 py-2 text-sm" role="alert" style="color: var(--danger); background: color-mix(in srgb, var(--danger) 8%, transparent);">{{ $message }}</div>
                    @enderror

                    {{-- Quick intent buttons --}}
                    <div class="px-4 py-3 border-t flex gap-2 flex-wrap" style="border-color: var(--border);">
                        @foreach (['tighten' => 'Tighten', 'rewrite_section' => 'Rewrite', 'add_citation' => 'Add citation'] as $intent => $label)
                            <form method="POST" action="{{ route('app.drafts.edit', $draft) }}" class="inline">
                                @csrf
                                <input type="hidden" name="intent" value="{{ $intent }}">
                                <input type="hidden" name="instruction" value="{{ $label }} the entire draft per its archetype.">
                                <button type="submit" class="btn btn-secondary text-xs" style="min-height: 32px; padding: 0.25rem 0.75rem;">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>

                    {{-- Free-form chat input --}}
                    <form method="POST" action="{{ route('app.drafts.edit', $draft) }}" class="border-t" style="border-color: var(--border);">
                        @csrf
                        <input type="hidden" name="intent" value="free_form">
                        <label for="instruction" class="sr-only">Edit instruction</label>
                        <textarea id="instruction" name="instruction" rows="3" required
                                  placeholder="e.g. add a ground about cooperation with investigation"
                                  class="w-full px-3 py-2 text-sm border-0"
                                  style="background: var(--surface); color: var(--fg); resize: none;"></textarea>
                        <div class="px-3 py-2 flex items-center justify-between" style="background: var(--surface-2);">
                            <span class="text-xs" style="color: var(--fg-muted);">Enter to send</span>
                            <button type="submit" class="btn btn-primary text-xs" style="min-height: 32px; padding: 0.25rem 1rem;">Send →</button>
                        </div>
                    </form>
                </div>

                {{-- Snapshots / undo --}}
                @if($draft->snapshots()->count() > 1)
                    <div class="card mt-4">
                        <h3 class="font-serif text-sm font-semibold mb-2" style="color: var(--fg);">⏱ Snapshots</h3>
                        <ul class="space-y-1.5 text-xs" role="list">
                            @foreach($draft->snapshots()->limit(8)->get() as $snap)
                                <li class="flex items-center justify-between gap-2">
                                    <span style="color: var(--fg-muted);">{{ $snap->created_at->diffForHumans() }} — {{ \Illuminate\Support\Str::limit($snap->label, 30) }}</span>
                                    <form method="POST" action="{{ route('app.drafts.revert', $draft) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="snapshot_id" value="{{ $snap->id }}">
                                        <button type="submit" class="btn-ghost text-xs" style="color: var(--link);">Revert</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </section>

    {{-- Inline selection toolbar JS --}}
    <script>
    (function () {
        var body = document.getElementById('draft-body');
        var toolbar = document.getElementById('selection-toolbar');
        var form = document.getElementById('inline-edit-form');
        if (!body || !toolbar || !form) return;

        var lastSel = null;

        function hideToolbar() {
            toolbar.style.display = 'none';
            lastSel = null;
        }

        function getSelectionInfo() {
            var sel = window.getSelection();
            if (!sel || sel.rangeCount === 0) return null;
            var text = sel.toString().trim();
            if (text.length < 5) return null;
            var range = sel.getRangeAt(0);
            if (!body.contains(range.commonAncestorContainer)) return null;
            return { text: text, range: range };
        }

        function positionToolbar(rect) {
            toolbar.style.display = 'flex';
            toolbar.style.top = (window.scrollY + rect.top - 50) + 'px';
            toolbar.style.left = Math.max(8, window.scrollX + rect.left + (rect.width / 2) - 150) + 'px';
        }

        document.addEventListener('mouseup', function () {
            setTimeout(function () {
                var info = getSelectionInfo();
                if (!info) { hideToolbar(); return; }
                lastSel = info;
                positionToolbar(info.range.getBoundingClientRect());
            }, 10);
        });

        document.addEventListener('mousedown', function (e) {
            if (toolbar.contains(e.target)) return;
            // hide on click outside selection
            setTimeout(function () {
                var sel = window.getSelection();
                if (!sel || sel.toString().trim().length < 5) hideToolbar();
            }, 10);
        });

        toolbar.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-intent]');
            if (!btn || !lastSel) return;
            var intent = btn.dataset.intent;
            var instruction = '';
            if (intent === 'free_form') {
                instruction = window.prompt('What should the AI do with the selected text?', '');
                if (!instruction) return;
            } else if (intent === 'add_citation') {
                instruction = window.prompt('Briefly describe the claim that needs a citation:', '') || '';
            } else if (intent === 'rewrite_section') {
                instruction = window.prompt('How should this be rewritten?', 'rewrite to be more precise') || 'rewrite to be more precise';
            } else if (intent === 'tighten') {
                instruction = 'tighten this passage by ~30-40% without losing legal substance';
            }

            // Compute char offsets within the markdown source — approximation using
            // textContent of the rendered article. The backend will use selection_text
            // as the canonical reference if start/end aren't an exact match.
            var fullText = body.innerText;
            var selText = lastSel.text;
            var start = fullText.indexOf(selText);
            var end = start >= 0 ? start + selText.length : null;

            document.getElementById('inline-intent').value = intent;
            document.getElementById('inline-instruction').value = instruction;
            document.getElementById('inline-selection-text').value = selText;
            document.getElementById('inline-selection-start').value = start >= 0 ? start : '';
            document.getElementById('inline-selection-end').value = end != null ? end : '';
            hideToolbar();
            form.submit();
        });

        // Hide toolbar on scroll/resize
        ['scroll', 'resize'].forEach(function (evt) {
            window.addEventListener(evt, hideToolbar, { passive: true });
        });
    })();
    </script>
</x-layouts.app-shell>
