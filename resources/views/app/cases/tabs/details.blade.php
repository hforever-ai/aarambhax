{{-- Details tab — case info + hearings --}}
<div class="case-details-grid">
    <div>
        <div class="case-card">
            <h2 class="case-section-title">Case information</h2>

            <dl class="case-detail-list">
                @if($case->forum)
                    <div><dt>Forum</dt><dd>{{ str_replace('_', ' ', strtoupper($case->forum)) }}</dd></div>
                @endif
                @if($case->court_name)
                    <div><dt>Court</dt><dd>{{ $case->court_name }}</dd></div>
                @endif
                @if($case->case_no)
                    <div><dt>Case no.</dt><dd class="font-mono">{{ $case->case_no }}</dd></div>
                @endif
                @if($case->jurisdiction)
                    <div><dt>Jurisdiction</dt><dd>{{ $case->jurisdiction }}</dd></div>
                @endif
                @if($case->category)
                    <div><dt>Category</dt><dd>{{ $case->category }}</dd></div>
                @endif
                @if($case->opposing_party)
                    <div><dt>Opposing party</dt><dd>{{ $case->opposing_party }}</dd></div>
                @endif
                @if($case->client)
                    <div><dt>Client</dt><dd>{{ $case->client->name }}</dd></div>
                @endif
                <div><dt>Created</dt><dd>{{ $case->created_at->format('d M Y') }}</dd></div>
            </dl>

            @if($case->khasra_no || $case->khata_no || $case->gram || $case->tehsil || $case->jila)
                <div class="case-detail-section">
                    <h3 class="case-detail-section-title">Land record</h3>
                    <dl class="case-detail-list">
                        @if($case->khasra_no)<div><dt>Khasra no.</dt><dd class="font-mono">{{ $case->khasra_no }}</dd></div>@endif
                        @if($case->khata_no)<div><dt>Khata no.</dt><dd class="font-mono">{{ $case->khata_no }}</dd></div>@endif
                        @if($case->gram)<div><dt>Gram</dt><dd>{{ $case->gram }}</dd></div>@endif
                        @if($case->tehsil)<div><dt>Tehsil</dt><dd>{{ $case->tehsil }}</dd></div>@endif
                        @if($case->jila)<div><dt>Jila</dt><dd>{{ $case->jila }}</dd></div>@endif
                    </dl>
                </div>
            @endif

            <div class="case-detail-actions">
                @if($case->status === 'active')
                    <form method="POST" action="{{ route('app.cases.close', $case) }}">
                        @csrf
                        <button type="submit" class="case-btn-ghost">Close case</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('app.cases.reopen', $case) }}">
                        @csrf
                        <button type="submit" class="case-btn-ghost">Reopen case</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="case-card">
            <h2 class="case-section-title">Hearings</h2>
            @if($hearings->isEmpty())
                <p class="case-section-sub" style="margin-bottom: 0;">No hearings recorded yet.</p>
            @else
                <ul class="case-hearing-list">
                    @foreach($hearings as $h)
                        <li class="case-hearing-item">
                            <div class="case-hearing-date">
                                <span class="case-hearing-day">{{ $h->date->format('d') }}</span>
                                <span class="case-hearing-mon">{{ $h->date->format('M Y') }}</span>
                            </div>
                            <div class="min-w-0">
                                <div class="case-hearing-purpose">{{ $h->purpose ?? 'Hearing' }}</div>
                                @if($h->notes)
                                    <div class="case-hearing-notes">{{ \Illuminate\Support\Str::limit($h->notes, 120) }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<style>
    .case-details-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    @media (min-width: 900px) {
        .case-details-grid { grid-template-columns: 1.4fr 1fr; }
    }

    .case-detail-list {
        display: grid;
        gap: 0.625rem;
        margin: 0;
    }
    .case-detail-list > div {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: 1rem;
        font-size: 0.9375rem;
        line-height: 1.5;
    }
    .case-detail-list dt {
        color: var(--fg-muted);
        font-size: 0.8125rem;
        margin: 0;
    }
    .case-detail-list dd {
        color: var(--fg);
        margin: 0;
    }
    .case-detail-section {
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border);
    }
    .case-detail-section-title {
        font-family: var(--font-serif);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--fg-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin: 0 0 0.875rem;
    }
    .case-detail-actions {
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border);
    }

    .case-hearing-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.875rem;
    }
    .case-hearing-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }
    .case-hearing-date {
        flex-shrink: 0;
        text-align: center;
        background: color-mix(in srgb, var(--accent) 8%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--accent) 25%, var(--border));
        border-radius: 8px;
        padding: 0.4rem 0.6rem;
        min-width: 56px;
    }
    .case-hearing-day {
        display: block;
        font-family: var(--font-serif);
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--fg);
        line-height: 1;
    }
    .case-hearing-mon {
        display: block;
        font-size: 0.6875rem;
        color: var(--fg-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 0.25rem;
    }
    .case-hearing-purpose {
        font-weight: 500;
        color: var(--fg);
        font-size: 0.9375rem;
    }
    .case-hearing-notes {
        font-size: 0.8125rem;
        color: var(--fg-muted);
        margin-top: 0.25rem;
        line-height: 1.4;
    }
</style>
