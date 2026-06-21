<x-layouts.app-shell :title="$case->title.' — Aarambhax'">
    <section class="container-page py-10 max-w-5xl">
        <a href="{{ route('app.cases.index') }}" class="text-sm" style="color: var(--fg-muted);">← All cases</a>

        <div class="flex items-end justify-between mt-2 mb-6 flex-wrap gap-3">
            <div>
                <h1 class="text-3xl sm:text-4xl font-serif font-medium" style="color: var(--fg);">{{ $case->title }}</h1>
                <div class="flex items-center gap-2 mt-2 flex-wrap text-xs">
                    <span class="badge badge-accent">{{ str_replace('_', ' ', $case->forum) }}</span>
                    @if($case->case_no)<span class="badge font-mono" style="background: var(--surface-2);">{{ $case->case_no }}</span>@endif
                    <span class="badge" style="background: {{ $case->status === 'active' ? 'color-mix(in srgb, var(--success) 18%, transparent)' : 'var(--surface-2)' }}; color: {{ $case->status === 'active' ? 'var(--success)' : 'var(--fg-muted)' }};">{{ $case->status }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                @if($case->status === 'active')
                    <form method="POST" action="{{ route('app.cases.close', $case) }}" class="inline">@csrf
                        <button type="submit" class="btn btn-ghost text-sm">Close case</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('app.cases.reopen', $case) }}" class="inline">@csrf
                        <button type="submit" class="btn btn-secondary text-sm">Reopen</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="card">
                <h2 class="font-serif text-lg font-semibold mb-3" style="color: var(--fg);">Court</h2>
                <dl class="text-sm space-y-2">
                    @if($case->court_name)<div><dt class="font-medium" style="color: var(--fg-muted);">Court</dt><dd style="color: var(--fg);">{{ $case->court_name }}</dd></div>@endif
                    @if($case->jurisdiction)<div><dt class="font-medium" style="color: var(--fg-muted);">Jurisdiction</dt><dd style="color: var(--fg);">{{ $case->jurisdiction }}</dd></div>@endif
                    @if($case->opposing_party)<div><dt class="font-medium" style="color: var(--fg-muted);">Opposing party</dt><dd style="color: var(--fg);">{{ $case->opposing_party }}</dd></div>@endif
                    @if($case->category)<div><dt class="font-medium" style="color: var(--fg-muted);">Category</dt><dd style="color: var(--fg);">{{ $case->category }}</dd></div>@endif
                </dl>
            </div>

            @if($case->forum === 'cg_revenue' && ($case->khasra_no || $case->gram))
                <div class="card">
                    <h2 class="font-serif text-lg font-semibold mb-3" style="color: var(--fg);">Land details</h2>
                    <dl class="text-sm space-y-2">
                        @foreach (['khasra_no' => 'Khasra', 'khata_no' => 'Khata', 'khatauni_no' => 'Khatauni', 'gram' => 'Gram', 'tehsil' => 'Tehsil', 'jila' => 'Jila'] as $col => $label)
                            @if($case->$col)
                                <div><dt class="font-medium" style="color: var(--fg-muted);">{{ $label }}</dt><dd class="font-mono" style="color: var(--fg);">{{ $case->$col }}</dd></div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            @endif

            @if($case->client)
                <div class="card">
                    <h2 class="font-serif text-lg font-semibold mb-3" style="color: var(--fg);">Client</h2>
                    <p class="text-sm" style="color: var(--fg);"><strong>{{ $case->client->name }}</strong></p>
                    @if($case->client->phone)<p class="text-sm" style="color: var(--fg-muted);">{{ $case->client->phone }}</p>@endif
                </div>
            @endif
        </div>

        {{-- AI Workspace: Documents · Analyses · Chat --}}
        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('app.cases.analyses.create', $case) }}" class="card block hover:bg-surface-2 transition-colors no-underline" aria-label="Run a new strategic analysis on this case">
                <p class="text-xs uppercase tracking-wider text-fg-muted">Documents</p>
                <p class="text-3xl font-serif font-semibold text-fg mt-1">{{ $case->documents()->count() }}</p>
                <p class="text-sm text-fg-muted mt-2">+ Add documents & run analysis</p>
            </a>
            <a href="{{ route('app.cases.analyses.create', $case) }}" class="card block hover:bg-surface-2 transition-colors no-underline">
                <p class="text-xs uppercase tracking-wider text-fg-muted">Analyses</p>
                <p class="text-3xl font-serif font-semibold text-fg mt-1">{{ $case->analyses()->count() }}</p>
                <p class="text-sm text-fg-muted mt-2">→ Strategic analysis (Pro 2.5)</p>
            </a>
            <a href="{{ route('app.cases.chat.show', $case) }}" class="card block hover:bg-surface-2 transition-colors no-underline">
                <p class="text-xs uppercase tracking-wider text-fg-muted">Chat</p>
                <p class="text-3xl font-serif font-semibold text-fg mt-1">{{ $case->conversations()->count() }}</p>
                <p class="text-sm text-fg-muted mt-2">💬 Discuss with AI · convert to draft</p>
            </a>
        </div>

        @if($case->analyses->isNotEmpty())
            <div class="mt-6">
                <h2 class="h2-section mb-3">Recent analyses</h2>
                <ul role="list" class="space-y-2">
                    @foreach($case->analyses()->latest()->limit(5)->get() as $analysis)
                        <li class="card flex items-center justify-between gap-3" style="padding: 0.875rem 1.25rem;">
                            <a href="{{ route('app.cases.analyses.show', [$case, $analysis]) }}" class="font-medium text-link">
                                {{ \Illuminate\Support\Str::limit($analysis->title, 70) }}
                            </a>
                            <span class="text-xs text-fg-muted">{{ str_replace('_', ' ', $analysis->analysis_type) }} · {{ $analysis->updated_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-10">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xl font-serif font-medium" style="color: var(--fg);">Hearings</h2>
                <a href="{{ route('app.hearings.create', ['case_id' => $case->id]) }}" class="btn btn-secondary text-sm">+ Add hearing</a>
            </div>
            @if($case->hearings->isEmpty())
                <p class="card text-center py-6 text-sm" style="color: var(--fg-muted);">No hearings logged. Add one to enable Telegram reminders.</p>
            @else
                <div class="card" style="padding: 0;">
                    <ul role="list">
                        @foreach($case->hearings as $h)
                            <li class="flex items-center justify-between gap-3 px-4 py-3" style="border-bottom: 1px solid var(--border);">
                                <div>
                                    <p class="text-sm font-medium" style="color: var(--fg);">{{ $h->date->format('d M Y') }}{{ $h->time ? ' · '.$h->time : '' }}</p>
                                    <p class="text-xs" style="color: var(--fg-muted);">{{ $h->purpose ?: '—' }} {{ $h->court ? ' · '.$h->court : '' }}</p>
                                </div>
                                <span class="text-xs" style="color: var(--fg-muted);">{{ $h->reminded_at ? '🔔 reminded' : 'pending' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Work items — primary action surface for this case --}}
        <div class="mt-10">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-xl font-serif font-medium" style="color: var(--fg);">Work</h2>
                    <p class="text-xs mt-0.5" style="color: var(--fg-muted);">Discrete actions on this case — case brief, timeline, plain explainer, drafts.</p>
                </div>
                @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                    <a href="{{ route('app.cases.karyas.create', $case) }}" class="btn btn-primary text-sm">+ New Work</a>
                @else
                    <span class="btn btn-primary text-sm opacity-50 cursor-not-allowed" title="Awaiting admin approval" aria-disabled="true">+ New Work</span>
                @endif
            </div>
            @php($karyas = $case->karyas()->latest()->limit(20)->get())
            @if($karyas->isEmpty())
                <div class="card text-center py-8" style="color: var(--fg-muted);">
                    <p class="text-sm mb-3">No work items run yet on this case.</p>
                    @if(auth()->user()->isApproved() || auth()->user()->isAdmin())
                        <a href="{{ route('app.cases.karyas.create', $case) }}" class="btn btn-primary text-sm">Run your first Work</a>
                    @else
                        <span class="btn btn-primary text-sm opacity-50 cursor-not-allowed" title="Awaiting admin approval" aria-disabled="true">Run your first Work</span>
                    @endif
                </div>
            @else
                <ul role="list" class="space-y-2">
                    @foreach($karyas as $k)
                        @php($entry = \App\Services\AarambhAi\KaryaCatalogue::get($k->type))
                        <li class="card flex items-center justify-between gap-3" style="padding: 0.875rem 1.25rem;">
                            <div class="min-w-0">
                                <a href="{{ route('app.cases.karyas.show', [$case, $k]) }}" class="font-medium" style="color: var(--link);">{{ \Illuminate\Support\Str::limit($k->title, 70) }}</a>
                                <div class="text-xs mt-0.5" style="color: var(--fg-muted);">
                                    {{ $entry['label_en'] ?? $k->type }} · {{ strtoupper($k->language) }}
                                    @if($k->pipeline_status === 'running')
                                        · <span style="color: var(--accent);">⟳ running ({{ $k->pipeline_progress ?? 0 }}%)</span>
                                    @elseif($k->pipeline_status === 'queued')
                                        · <span style="color: var(--fg-muted);">queued</span>
                                    @elseif($k->pipeline_status === 'failed')
                                        · <span style="color: #dc2626;">failed</span>
                                    @else
                                        · {{ $k->updated_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Drafts — kept accessible but no longer the primary CTA --}}
        <div class="mt-10">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xl font-serif font-medium" style="color: var(--fg);">Drafts in this case</h2>
            </div>
            @if($case->drafts->isEmpty())
                <p class="card text-center py-6 text-sm" style="color: var(--fg-muted);">No drafts yet for this case. Run a Work item above and convert it to a draft when ready.</p>
            @else
                <ul role="list" class="space-y-2">
                    @foreach($case->drafts as $d)
                        <li class="card flex items-center justify-between gap-3" style="padding: 0.875rem 1.25rem;">
                            <a href="{{ route('app.drafts.show', $d) }}" class="font-medium" style="color: var(--link);">{{ \Illuminate\Support\Str::limit($d->title, 70) }}</a>
                            <span class="text-xs" style="color: var(--fg-muted);">{{ strtoupper($d->language) }} · {{ $d->updated_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</x-layouts.app-shell>
