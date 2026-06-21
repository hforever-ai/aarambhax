{{-- Analysis tab — pick docs (default all) → run → see output --}}
@php
    $isApproved = auth()->user()->isApproved() || auth()->user()->isAdmin();
    $hasDocs = $documents->isNotEmpty();
@endphp

<div class="case-card">
    <h2 class="case-section-title">Run analysis</h2>
    <p class="case-section-sub">
        Aarambh reads your selected documents and produces strategic analysis — issues, options,
        risks, missing facts, recommended next filing. Costs ₹0 (free tier, PII-redacted).
    </p>

    @if(! $hasDocs)
        <div class="case-empty-state">
            <p>You need at least one ingested document. Upload some on the <a href="{{ route('app.cases.tab.documents', $case) }}">Documents tab</a>.</p>
        </div>
    @elseif($isApproved)
        <form method="POST" action="{{ route('app.cases.karyas.store', $case) }}" class="analysis-form">
            @csrf
            <input type="hidden" name="type" value="case_brief">
            <input type="hidden" name="language" value="en">

            <div class="doc-picker">
                <div class="doc-picker-header">
                    <span class="doc-picker-label">Documents to use</span>
                    <button type="button" class="doc-picker-toggle-all" id="ana-toggle-all">All ({{ $documents->count() }})</button>
                </div>
                <div class="doc-picker-chips">
                    @foreach($documents as $doc)
                        <label class="doc-chip">
                            <input type="checkbox" name="document_ids[]" value="{{ $doc->id }}" checked>
                            <span class="doc-chip-name">{{ \Illuminate\Support\Str::limit($doc->original_filename, 40) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="analysis-instruction">
                <label for="ana-inst" class="analysis-instruction-label">What to focus on (optional)</label>
                <textarea
                    id="ana-inst"
                    name="user_instruction"
                    rows="2"
                    placeholder="e.g. limitation issue, recommended next filing, gaps in evidence"
                ></textarea>
            </div>

            <div class="analysis-actions">
                <button type="submit" class="case-btn-primary">Run analysis →</button>
            </div>
        </form>
    @else
        <div class="documents-locked">
            <p>Awaiting admin approval.</p>
        </div>
    @endif
</div>

@if($pastWorks->isNotEmpty())
    <div class="past-works">
        <h3 class="past-works-title">Past analyses</h3>
        @foreach($pastWorks as $w)
            <a href="{{ route('app.cases.karyas.show', [$case, $w]) }}" class="case-card case-card-hoverable past-work-row">
                <div class="past-work-icon">📋</div>
                <div class="past-work-main">
                    <div class="past-work-title">{{ \Illuminate\Support\Str::limit($w->title, 70) }}</div>
                    <div class="past-work-meta">
                        @if($w->tier === 'free')
                            <span class="tier-badge tier-free">FREE</span>
                        @else
                            <span class="tier-badge tier-paid">PAID</span>
                        @endif
                        <span>{{ $w->updated_at->diffForHumans() }}</span>
                        @if($w->pipeline_status === 'running')
                            <span class="pipeline-running">⟳ running ({{ $w->pipeline_progress }}%)</span>
                        @elseif($w->pipeline_status === 'failed')
                            <span class="pipeline-failed">✗ failed</span>
                        @endif
                    </div>
                </div>
                <span class="past-work-arrow">→</span>
            </a>
        @endforeach
    </div>
@endif

@include('app.cases.tabs._shared_styles')
