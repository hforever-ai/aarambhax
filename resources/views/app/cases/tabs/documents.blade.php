{{-- Documents tab — upload-only, no analysis trigger --}}

@php
    // A doc is processing when it has no ocr_text AND has never been touched
    // by the ingest job (ingested_at IS NULL). Once ingested_at is set, the
    // job ran — either ocr_text is populated (Ready) or it's still empty (Failed).
    $hasProcessing = $documents->contains(fn ($d) => empty($d->ocr_text) && empty($d->ingested_at));
    $isApproved = auth()->user()->isApproved() || auth()->user()->isAdmin();
@endphp

<div class="case-card">
    <div class="documents-header">
        <div>
            <h2 class="case-section-title" style="margin-bottom: 0.375rem;">Documents</h2>
            <p class="case-section-sub" style="margin-bottom: 0;">
                Upload PDFs or images. Each is read once and reused across Analysis, Draft, and Brainstorm.
            </p>
        </div>
        <div class="documents-count">
            <span class="documents-count-num">{{ $documents->count() }}</span>
            <span class="documents-count-label">{{ \Illuminate\Support\Str::plural('document', $documents->count()) }}</span>
        </div>
    </div>

    @if($errors->any())
        <div class="documents-error" role="alert">
            @foreach($errors->all() as $err)
                <p>• {{ $err }}</p>
            @endforeach
        </div>
    @endif

    @if($isApproved)
        <form
            id="doc-upload-form"
            method="POST"
            action="{{ route('app.cases.documents.store', $case) }}"
            enctype="multipart/form-data"
            class="documents-dropzone"
        >
            @csrf
            <input
                type="file"
                name="files[]"
                id="doc-file-input"
                multiple
                accept="application/pdf,image/*"
                class="documents-dropzone-input"
                aria-label="Upload documents"
            >
            <div class="documents-dropzone-inner" id="doc-dropzone-inner">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                <p class="documents-dropzone-title">Drop files here or click to upload</p>
                <div class="documents-dropzone-limits">
                    <span class="documents-dropzone-pill">Max 15 MB per file</span>
                    <span class="documents-dropzone-pill">Up to 10 files</span>
                </div>
                <p class="documents-dropzone-hint">PDF, JPG, PNG, WebP, HEIC · larger PDFs (e.g. full court judgments) — please split them first</p>
            </div>
            <ul class="documents-dropzone-selected" id="doc-selected" hidden></ul>
            <div class="documents-dropzone-actions" id="doc-actions" hidden>
                <button type="button" class="case-btn-ghost" id="doc-clear">Clear</button>
                <button type="submit" class="case-btn-primary" id="doc-submit">Upload</button>
            </div>
        </form>
    @else
        <div class="documents-locked" role="status">
            <p>Document upload requires admin approval. You'll receive an email once approved.</p>
        </div>
    @endif
</div>

@if($documents->isNotEmpty())
    <div class="documents-list">
        @foreach($documents as $doc)
            @php
                // ocr_text populated → Ready. Otherwise look at ingested_at:
                //   IS NULL → still processing
                //   IS SET  → ingest job ran but produced no text → Failed
                $isReady = ! empty($doc->ocr_text);
                $isProcessing = ! $isReady && empty($doc->ingested_at);
                $isFailed = ! $isReady && ! empty($doc->ingested_at);
                $sizeStr = $doc->bytes ? round($doc->bytes / 1024, 0).' KB' : '';
                if ($doc->bytes > 1024 * 1024) {
                    $sizeStr = round($doc->bytes / 1024 / 1024, 1).' MB';
                }
            @endphp
            <div class="case-card case-card-hoverable doc-row" data-doc-status="{{ $isReady ? 'ready' : ($isFailed ? 'failed' : 'processing') }}">
                <div class="doc-row-icon">
                    @if(str_contains($doc->mime_type ?? '', 'pdf'))
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    @else
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    @endif
                </div>
                <div class="doc-row-main">
                    <div class="doc-row-name">{{ \Illuminate\Support\Str::limit($doc->original_filename, 80) }}</div>
                    <div class="doc-row-meta">
                        @if($doc->detected_doc_type)
                            <span class="doc-row-type">{{ str_replace('_', ' ', $doc->detected_doc_type) }}</span>
                        @endif
                        @if($doc->language)
                            <span>{{ strtoupper($doc->language) }}</span>
                        @endif
                        @if($sizeStr)
                            <span>{{ $sizeStr }}</span>
                        @endif
                        <span>{{ $doc->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="doc-row-status">
                    @if($isReady)
                        <span class="doc-status doc-status-ready">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Ready
                        </span>
                    @elseif($isFailed)
                        <span class="doc-status doc-status-failed" title="Ingest failed — likely the file is too large (>10 MB) or had unreadable pages. Delete and re-upload a smaller file.">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Failed
                        </span>
                    @else
                        <span class="doc-status doc-status-processing">
                            <span class="doc-status-dot"></span>
                            Reading…
                        </span>
                    @endif
                    @if($isApproved)
                        <form method="POST" action="{{ route('app.cases.documents.destroy', [$case, $doc]) }}" onsubmit="return confirm('Delete this document?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="doc-row-delete" aria-label="Delete document">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($hasProcessing)
        <div class="doc-processing-note" id="doc-processing-note">
            <span class="doc-status-dot"></span>
            Some documents are being read in the background. This page refreshes automatically.
        </div>
    @endif
@endif

<style>
    .documents-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .documents-count {
        text-align: right;
        flex-shrink: 0;
    }
    .documents-count-num {
        display: block;
        font-family: var(--font-serif);
        font-size: 2rem;
        font-weight: 600;
        color: var(--fg);
        line-height: 1;
    }
    .documents-count-label {
        display: block;
        font-size: 0.75rem;
        color: var(--fg-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 0.125rem;
    }

    .documents-error {
        background: color-mix(in srgb, var(--danger) 8%, transparent);
        border: 1px solid color-mix(in srgb, var(--danger) 30%, var(--border));
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        color: var(--danger);
        margin-bottom: 1rem;
    }
    .documents-error p { margin: 0; }

    .documents-locked {
        text-align: center;
        padding: 2rem 1rem;
        background: color-mix(in srgb, var(--warning) 5%, var(--surface));
        border: 1px dashed color-mix(in srgb, var(--warning) 35%, var(--border));
        border-radius: 10px;
    }
    .documents-locked p {
        margin: 0;
        font-size: 0.9375rem;
        color: var(--fg-muted);
    }

    /* Dropzone */
    .documents-dropzone {
        position: relative;
        border: 1.5px dashed var(--border);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        background: color-mix(in srgb, var(--accent) 2%, var(--surface));
        transition: border-color 200ms ease-out, background 200ms ease-out;
    }
    .documents-dropzone:hover,
    .documents-dropzone.is-dragover {
        border-color: var(--accent);
        background: color-mix(in srgb, var(--accent) 8%, var(--surface));
    }
    .documents-dropzone-input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 1;
    }
    .documents-dropzone-inner {
        position: relative;
        z-index: 0;
        color: var(--fg-muted);
    }
    .documents-dropzone-inner svg {
        color: var(--accent);
    }
    .documents-dropzone-title {
        font-family: var(--font-serif);
        font-size: 1.0625rem;
        font-weight: 500;
        color: var(--fg);
        margin: 0.75rem 0 0.5rem;
    }
    .documents-dropzone-limits {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.4rem;
        margin: 0 0 0.5rem;
    }
    .documents-dropzone-pill {
        display: inline-flex;
        align-items: center;
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.1875rem 0.625rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 14%, transparent);
        color: var(--accent);
    }
    .documents-dropzone-hint {
        font-size: 0.75rem;
        color: var(--fg-muted);
        margin: 0;
        line-height: 1.5;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }
    .documents-dropzone-selected {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
        text-align: left;
        font-size: 0.875rem;
        color: var(--fg);
        list-style: none;
        padding-left: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .documents-dropzone-selected .doc-size {
        color: var(--fg-muted);
        margin-left: 0.375rem;
    }
    .documents-dropzone-selected .doc-too-big {
        color: var(--danger);
    }
    .documents-dropzone-selected .doc-too-big .doc-size {
        color: var(--danger);
        font-weight: 500;
    }
    .documents-dropzone-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        position: relative;
        z-index: 2;
    }

    /* Document rows */
    .documents-list {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .doc-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        margin-top: 0 !important;
    }
    .doc-row-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--link) 10%, transparent);
        color: var(--link);
        border-radius: 8px;
    }
    .doc-row-main {
        flex: 1;
        min-width: 0;
    }
    .doc-row-name {
        font-weight: 500;
        color: var(--fg);
        font-size: 0.9375rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .doc-row-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.625rem;
        font-size: 0.75rem;
        color: var(--fg-muted);
        margin-top: 0.25rem;
    }
    .doc-row-type {
        background: color-mix(in srgb, var(--accent) 12%, transparent);
        color: var(--accent);
        padding: 0.0625rem 0.5rem;
        border-radius: 4px;
        text-transform: uppercase;
        font-size: 0.6875rem;
        letter-spacing: 0.04em;
        font-weight: 600;
    }
    .doc-row-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }
    .doc-status {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.25rem 0.625rem;
        border-radius: 999px;
    }
    .doc-status-ready {
        background: color-mix(in srgb, var(--success) 12%, transparent);
        color: var(--success);
    }
    .doc-status-processing {
        background: color-mix(in srgb, var(--warning) 12%, transparent);
        color: var(--warning);
    }
    .doc-status-failed {
        background: color-mix(in srgb, var(--danger) 12%, transparent);
        color: var(--danger);
    }
    .doc-status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: currentColor;
        border-radius: 50%;
        animation: doc-pulse 1.4s ease-in-out infinite;
    }
    @keyframes doc-pulse {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 1; }
    }
    .doc-row-delete {
        background: none;
        border: none;
        padding: 0.5rem;
        color: var(--fg-muted);
        border-radius: 6px;
        cursor: pointer;
        transition: color 150ms ease-out, background 150ms ease-out;
    }
    .doc-row-delete:hover {
        color: var(--danger);
        background: color-mix(in srgb, var(--danger) 8%, transparent);
    }

    .doc-processing-note {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
        padding: 0.625rem 0.875rem;
        background: color-mix(in srgb, var(--warning) 8%, transparent);
        border: 1px solid color-mix(in srgb, var(--warning) 30%, var(--border));
        border-radius: 8px;
        font-size: 0.8125rem;
        color: var(--fg);
    }
    .doc-processing-note .doc-status-dot {
        color: var(--warning);
    }
</style>

<script>
(function () {
    const form = document.getElementById('doc-upload-form');
    if (! form) return;

    const input = document.getElementById('doc-file-input');
    const selected = document.getElementById('doc-selected');
    const actions = document.getElementById('doc-actions');
    const submitBtn = document.getElementById('doc-submit');
    const clearBtn = document.getElementById('doc-clear');

    const MAX_BYTES = 15 * 1024 * 1024;     // 15 MB — matches server validation

    function renderSelected() {
        // Clear existing list children
        while (selected.firstChild) selected.removeChild(selected.firstChild);
        const files = Array.from(input.files || []);
        if (! files.length) {
            selected.hidden = true;
            actions.hidden = true;
            submitBtn.disabled = false;
            return;
        }
        selected.hidden = false;
        actions.hidden = false;

        let anyTooBig = false;
        // Build LI elements via DOM API — file names come from local FS but
        // are NEVER inserted as HTML; only as textContent. Safe vs XSS even
        // if a filename contains <script> or other markup.
        files.forEach(f => {
            const sizeKB = f.size / 1024;
            const sizeStr = sizeKB > 1024
                ? (sizeKB / 1024).toFixed(1) + ' MB'
                : Math.round(sizeKB) + ' KB';
            const tooBig = f.size > MAX_BYTES;
            if (tooBig) anyTooBig = true;

            const li = document.createElement('li');
            if (tooBig) li.classList.add('doc-too-big');
            const icon = document.createElement('span');
            icon.textContent = tooBig ? '⚠️ ' : '📄 ';
            const name = document.createElement('span');
            name.textContent = f.name;
            const size = document.createElement('span');
            size.className = 'doc-size';
            size.textContent = '· ' + sizeStr + (tooBig ? ' · too large (max 15 MB)' : '');
            li.appendChild(icon);
            li.appendChild(name);
            li.appendChild(size);
            selected.appendChild(li);
        });

        // Disable Upload if any file exceeds the cap — server would reject it anyway,
        // but flagging it here saves a round-trip and is a clearer failure mode.
        submitBtn.disabled = anyTooBig;
        submitBtn.title = anyTooBig
            ? 'One or more files exceed 15 MB. Remove or split them first.'
            : '';
    }

    input.addEventListener('change', renderSelected);
    clearBtn.addEventListener('click', () => {
        input.value = '';
        renderSelected();
    });

    form.addEventListener('dragover', (e) => {
        e.preventDefault();
        form.classList.add('is-dragover');
    });
    form.addEventListener('dragleave', () => form.classList.remove('is-dragover'));
    form.addEventListener('drop', (e) => {
        e.preventDefault();
        form.classList.remove('is-dragover');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            renderSelected();
        }
    });

    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading…';
    });

    // ── Quiet auto-refresh ─────────────────────────────────────────────
    // While any doc is still processing, refetch THIS tab every 30s and
    // swap in just the documents list — no full page reload, no flash,
    // dropzone stays usable. Stops as soon as everything is "Ready" or
    // "Failed" so we're not wastefully polling forever.
    const note = document.getElementById('doc-processing-note');
    if (! note) return;

    const REFRESH_MS = 30000;
    const tabUrl = window.location.pathname;
    let stopped = false;

    async function refreshTab() {
        if (stopped) return;
        try {
            const res = await fetch(tabUrl, {
                headers: { 'Accept': 'text/html' },
                credentials: 'same-origin',
            });
            if (! res.ok) return;
            const html = await res.text();

            // Parse the response and pluck out just the .documents-list +
            // .doc-processing-note block; keep everything else in the DOM
            // (including the dropzone, which user may have just clicked).
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newList = doc.querySelector('.documents-list');
            const oldList = document.querySelector('.documents-list');
            if (newList && oldList) {
                oldList.innerHTML = newList.innerHTML;
            } else if (newList && ! oldList) {
                // Was empty before; insert after the upload card
                const dropCard = document.querySelector('.documents-dropzone')?.closest('.case-card');
                if (dropCard) dropCard.after(newList);
            }

            // Update the count badge in header
            const newCount = doc.querySelector('.documents-count-num');
            const oldCount = document.querySelector('.documents-count-num');
            if (newCount && oldCount) oldCount.textContent = newCount.textContent;

            // Did anything finish? Check if processing note is still in the new HTML.
            const stillProcessing = doc.getElementById('doc-processing-note');
            if (! stillProcessing) {
                // All done — drop the note and stop polling.
                note.remove();
                stopped = true;
                return;
            }
        } catch (e) {
            // Network blip — try again next interval
        }
        setTimeout(refreshTab, REFRESH_MS);
    }

    setTimeout(refreshTab, REFRESH_MS);
})();
</script>
