<x-layouts.app-shell title="{{ $entry->draft_type ?? 'Catalogue entry' }} — Aarambhax Legal">
    <section class="container-page py-10">
        <nav aria-label="Breadcrumb" class="mb-4 text-sm">
            <a href="{{ route('app.catalogue.index') }}" class="text-link">← Back to catalogue</a>
        </nav>

        <header class="mb-8">
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                <span class="badge badge-accent">{{ str_replace('_', ' ', $entry->forum ?: 'forum?') }}</span>
                <span class="badge" style="background: color-mix(in srgb, var(--link) 18%, transparent); color: var(--link);">{{ strtoupper($entry->language ?: '?') }}</span>
                @if($entry->district)<span class="badge" style="background: color-mix(in srgb, var(--fg-muted) 14%, transparent); color: var(--fg-muted);">{{ $entry->district }}</span>@endif
                @if($entry->state)<span class="badge" style="background: color-mix(in srgb, var(--fg-muted) 14%, transparent); color: var(--fg-muted);">{{ $entry->state }}</span>@endif
            </div>
            <h1 class="h1-page">
                {{ $entry->draft_type ?: 'Untitled draft' }}
            </h1>
            @if($entry->court)
                <p class="mt-2 lead break-words">{{ $entry->court }}</p>
            @endif
            <dl class="mt-3 text-sm text-fg-muted grid grid-cols-1 sm:flex sm:flex-wrap gap-x-4 gap-y-1">
                <div class="flex gap-1 min-w-0">
                    <dt class="font-semibold shrink-0">Source:</dt>
                    <dd class="min-w-0"><code class="text-fg break-all">{{ $entry->source_filename }}</code></dd>
                </div>
                <div class="flex gap-1">
                    <dt class="font-semibold">Confidence:</dt>
                    <dd>{{ $entry->ai_confidence }}</dd>
                </div>
                <div class="flex gap-1">
                    <dt class="font-semibold">Tokens:</dt>
                    <dd>{{ $entry->tokens_input }} in / {{ $entry->tokens_output }} out</dd>
                </div>
            </dl>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <article class="card">
                <h2 class="h3-card mb-3">Required input documents</h2>
                @if($entry->required_inputs && count($entry->required_inputs))
                    <ul class="space-y-1">
                        @foreach($entry->required_inputs as $r)
                            <li class="text-sm text-fg">• <code>{{ $r }}</code></li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-fg-muted">None specified.</p>
                @endif
            </article>

            <article class="card">
                <h2 class="h3-card mb-3">Optional inputs</h2>
                @if($entry->optional_inputs && count($entry->optional_inputs))
                    <ul class="space-y-1">
                        @foreach($entry->optional_inputs as $r)
                            <li class="text-sm text-fg">• <code>{{ $r }}</code></li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-fg-muted">None.</p>
                @endif
            </article>

            @if($entry->forbidden_inputs && count($entry->forbidden_inputs))
                <article class="card">
                    <h2 class="h3-card mb-3">Forbidden / wrong-route inputs</h2>
                    <p class="text-xs text-fg-muted mb-2">If these are present, choose a different draft.</p>
                    <ul class="space-y-1">
                        @foreach($entry->forbidden_inputs as $r)
                            <li class="text-sm text-fg">• <code>{{ $r }}</code></li>
                        @endforeach
                    </ul>
                </article>
            @endif

            <article class="card">
                <h2 class="h3-card mb-3">Statutes invoked</h2>
                @if($entry->statutes && count($entry->statutes))
                    <ul class="space-y-1">
                        @foreach($entry->statutes as $s)
                            <li class="text-sm text-fg">
                                <span class="font-semibold">§{{ $s['section_no'] ?? '?' }}</span>
                                <span class="text-fg-muted">{{ $s['statute'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-fg-muted">None recorded.</p>
                @endif
            </article>

            @if($entry->template_outline && count($entry->template_outline))
                <article class="card md:col-span-2">
                    <h2 class="h3-card mb-3">Section outline (in order)</h2>
                    <ol class="space-y-1 list-decimal list-inside text-sm text-fg">
                        @foreach($entry->template_outline as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ol>
                </article>
            @endif

            @if($entry->cleaned_text)
                <details class="card md:col-span-2">
                    <summary class="cursor-pointer h3-card">
                        Show cleaned text (first 4000 chars)
                    </summary>
                    <pre class="mt-3 text-xs whitespace-pre-wrap text-fg-muted bg-surface-2 p-4 rounded" style="max-height: 60vh; overflow-y: auto;">{{ \Illuminate\Support\Str::limit($entry->cleaned_text, 4000) }}</pre>
                </details>
            @endif
        </div>
    </section>
</x-layouts.app-shell>
