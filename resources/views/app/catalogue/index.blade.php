<x-layouts.app-shell title="Draft Catalogue — Aarambhax Legal">
    <section class="container-page py-10">
        <header class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="h1-page">Draft Catalogue</h1>
                    <p class="mt-2 lead">
                        {{ $stats['total'] }} drafts catalogued from your chambers ·
                        AI fingerprint confidence: {{ $stats['avg_conf'] }} avg
                    </p>
                </div>
                <a href="{{ route('app.router.show') }}" class="btn btn-primary self-start sm:self-end shrink-0" aria-label="Open the draft router">
                    <span aria-hidden="true">→</span> Try the Router
                </a>
            </div>
        </header>

        {{-- Stats by forum --}}
        <h2 class="sr-only">Catalogue by forum</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-8" role="list">
            @foreach($stats['by_forum'] as $f => $c)
                <div class="card text-center" role="listitem">
                    <p class="text-xs uppercase tracking-wider text-fg-muted">{{ str_replace('_', ' ', $f ?: 'Unspecified') }}</p>
                    <p class="text-2xl font-serif font-semibold text-fg mt-1">{{ $c }}</p>
                </div>
            @endforeach
        </div>

        {{-- Filter form --}}
        <h2 class="sr-only">Filters</h2>
        <form method="get" class="card mb-6" role="search" aria-label="Filter catalogue">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div>
                    <label for="filter-q" class="form-label">Search</label>
                    <input id="filter-q" type="text" name="q" value="{{ $filters['q'] }}"
                           placeholder="draft type, court, file..." class="input">
                </div>
                <div>
                    <label for="filter-forum" class="form-label">Forum</label>
                    <select id="filter-forum" name="forum" class="input">
                        <option value="">All forums</option>
                        @foreach($facets['forums'] as $f)
                            <option value="{{ $f }}" @selected($filters['forum'] === $f)>{{ str_replace('_', ' ', $f) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-language" class="form-label">Language</label>
                    <select id="filter-language" name="language" class="input">
                        <option value="">All languages</option>
                        @foreach($facets['languages'] as $l)
                            <option value="{{ $l }}" @selected($filters['language'] === $l)>{{ strtoupper($l) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-district" class="form-label">District</label>
                    <select id="filter-district" name="district" class="input">
                        <option value="">All districts</option>
                        @foreach($facets['districts'] as $d)
                            <option value="{{ $d }}" @selected($filters['district'] === $d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Apply filters</button>
            </div>
        </form>

        {{-- Results --}}
        <div class="flex items-center justify-between mb-4">
            <h2 class="h2-section">
                {{ $entries->count() }} {{ \Illuminate\Support\Str::plural('draft', $entries->count()) }}
            </h2>
            @if(array_filter($filters))
                <a href="{{ route('app.catalogue.index') }}" class="text-sm text-link">Clear filters</a>
            @endif
        </div>

        @if($entries->isEmpty())
            <div class="card text-center py-12">
                <p class="text-fg-muted">No drafts match these filters. Try clearing them.</p>
            </div>
        @else
            <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 list-none p-0" role="list">
                @foreach($entries as $e)
                    <li>
                        <a href="{{ route('app.catalogue.show', $e->id) }}"
                           class="card block h-full no-underline hover:bg-surface-2 transition-colors"
                           aria-label="View {{ $e->draft_type }} catalogued from {{ $e->source_filename }}">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="badge badge-accent">{{ str_replace('_', ' ', $e->forum ?: 'forum?') }}</span>
                                <span class="badge" style="background: color-mix(in srgb, var(--link) 18%, transparent); color: var(--link);">{{ strtoupper($e->language ?: '?') }}</span>
                                @if($e->district)
                                    <span class="badge" style="background: color-mix(in srgb, var(--fg-muted) 14%, transparent); color: var(--fg-muted);">{{ $e->district }}</span>
                                @endif
                            </div>
                            <h3 class="h3-card mb-1">
                                {{ \Illuminate\Support\Str::limit($e->draft_type ?: 'Untitled draft', 80) }}
                            </h3>
                            @if($e->court)
                                <p class="text-sm text-fg-muted">{{ $e->court }}</p>
                            @endif
                            @if($e->required_inputs && count($e->required_inputs))
                                <p class="text-xs text-fg-muted mt-3">
                                    <span class="font-semibold uppercase tracking-wider">Needs:</span>
                                    {{ implode(', ', array_slice($e->required_inputs, 0, 5)) }}{{ count($e->required_inputs) > 5 ? '…' : '' }}
                                </p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app-shell>
