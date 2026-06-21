<x-layouts.app-shell title="Chat — {{ $case->title }}">
    <section class="container-page py-6 max-w-7xl">
        <nav aria-label="Breadcrumb" class="mb-3 text-sm">
            <a href="{{ route('app.cases.show', $case) }}" class="text-link">← {{ $case->title }}</a>
        </nav>

        <header class="mb-4">
            <h1 class="h1-page">💬 Discuss this case</h1>
            <p class="mt-2 lead">
                Ask anything about the case. Aarambh has read all documents on the case and remembers prior turns.
                When you're ready, click <strong>Convert to draft</strong> to turn the discussion into a filable draft.
            </p>
        </header>

        @if(session('edit_success'))
            <div class="card mb-4" style="border-color: var(--success); background: color-mix(in srgb, var(--success) 8%, var(--surface));" role="status">
                <p class="text-success">✓ {{ session('edit_success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="card mb-4" style="border-color: var(--danger); background: color-mix(in srgb, var(--danger) 8%, var(--surface));" role="alert">
                @foreach($errors->all() as $err)
                    <p class="text-danger text-sm">{{ $err }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Documents sidebar (left) --}}
            <aside class="lg:col-span-3">
                <div class="card sticky top-4">
                    <h2 class="h3-card mb-3">Case docs ({{ $documents->count() }})</h2>
                    @if($documents->isEmpty())
                        <p class="text-fg-muted text-sm">No documents yet.</p>
                        <a href="{{ route('app.cases.analyses.create', $case) }}" class="btn btn-secondary text-sm w-full mt-2">+ Add documents</a>
                    @else
                        <ul class="space-y-2 text-sm" role="list">
                            @foreach($documents as $doc)
                                <li class="flex items-start gap-2">
                                    <span aria-hidden="true">📄</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-fg break-words">{{ \Illuminate\Support\Str::limit($doc->original_filename, 40) }}</p>
                                        <p class="text-xs text-fg-muted">{{ $doc->detected_doc_type ?: 'unknown' }} · {{ strtoupper($doc->language ?: '?') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($conversation->context_summary_md)
                        <details class="mt-4 pt-4 border-t border-default">
                            <summary class="cursor-pointer text-sm font-semibold text-link">Earlier summary (folded)</summary>
                            <pre class="mt-2 text-xs whitespace-pre-wrap text-fg-muted" style="max-height: 240px; overflow-y: auto;">{{ $conversation->context_summary_md }}</pre>
                        </details>
                    @endif
                </div>
            </aside>

            {{-- Chat thread (right) --}}
            <main class="lg:col-span-9 space-y-3">
                <div id="chat-thread" class="card" style="min-height: 400px; max-height: 70vh; overflow-y: auto;">
                    @php($visibleMessages = $conversation->messages->where('summarised_into_context', false))
                    @forelse($visibleMessages as $msg)
                        @if($msg->role === 'user')
                            <div class="flex justify-end mb-3">
                                <div class="rounded-lg px-4 py-2 max-w-2xl text-sm" style="background: var(--primary); color: var(--primary-fg);">
                                    <p style="white-space: pre-wrap;">{{ $msg->content }}</p>
                                </div>
                            </div>
                        @else
                            <div class="flex justify-start mb-3">
                                <div class="rounded-lg px-4 py-3 max-w-2xl text-sm" style="background: var(--surface-2); color: var(--fg);">
                                    <p class="text-xs font-mono mb-1 text-fg-muted">{{ $msg->model_used ?: 'assistant' }} · {{ $msg->created_at?->diffForHumans() }}</p>
                                    <div class="prose-aarambhax" style="font-size: 0.875rem; line-height: 1.55;">
                                        {!! \Illuminate\Support\Str::markdown($msg->content) !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <p class="text-center text-fg-muted py-12">No messages yet. Type below to start the discussion.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('app.cases.chat.send', $case) }}" class="card">
                    @csrf
                    <label for="message" class="sr-only">Your message</label>
                    <textarea name="message" id="message" rows="3" required
                              placeholder="Ask anything: 'What's the strongest defence here?', 'List contradictions between the FIR and chargesheet', 'Should we file Quashing or Bail first?'"
                              class="input"></textarea>
                    <div class="mt-3 flex items-center justify-between flex-wrap gap-2">
                        <p class="text-xs text-fg-muted">
                            {{ $conversation->messages->where('summarised_into_context', false)->count() }} active messages ·
                            {{ number_format($conversation->total_tokens_in + $conversation->total_tokens_out) }} tokens used
                        </p>
                        <div class="flex items-center gap-2">
                            <button type="submit" formaction="{{ route('app.cases.chat.convert', $case) }}" formmethod="POST"
                                    class="btn btn-secondary"
                                    onclick="return confirm('Convert this discussion to a draft? You will be able to refine the draft afterwards.');">
                                Convert to draft →
                            </button>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </section>

    <script>
        // Auto-scroll chat thread to bottom on load
        (function () {
            var t = document.getElementById('chat-thread');
            if (t) t.scrollTop = t.scrollHeight;
        })();
    </script>
</x-layouts.app-shell>
