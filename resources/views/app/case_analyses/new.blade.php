<x-layouts.app-shell title="New Analysis — {{ $case->title }}">
    <section class="container-page py-10">
        <nav aria-label="Breadcrumb" class="mb-4 text-sm">
            <a href="{{ route('app.cases.show', $case) }}" class="text-link">← {{ $case->title }}</a>
        </nav>

        <header class="mb-6">
            <h1 class="h1-page">New strategic analysis</h1>
            <p class="mt-2 lead">
                Aarambh reads the documents already on this case (plus any new ones you add below)
                and produces a strategic analysis: issues, legal theories, risks, missing facts,
                and a recommended next step. Refine via chat. Convert to draft when ready.
            </p>
        </header>

        @if($errors->any())
            <div class="card mb-4" style="border-color: var(--danger); background: color-mix(in srgb, var(--danger) 8%, var(--surface));" role="alert">
                <p class="text-danger font-semibold mb-1">Could not start analysis</p>
                <ul class="text-sm space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('app.cases.analyses.store', $case) }}" enctype="multipart/form-data" class="card" id="analysis-form">
            @csrf

            @if($existingDocs->isNotEmpty())
                <fieldset class="border-0 p-0 mb-6">
                    <legend class="form-label">Documents already on this case ({{ $existingDocs->count() }})</legend>
                    <ul class="space-y-1 text-sm">
                        @foreach($existingDocs as $doc)
                            <li class="flex items-center gap-2">
                                <span aria-hidden="true">📄</span>
                                <span class="text-fg">{{ $doc->original_filename }}</span>
                                <span class="text-fg-muted text-xs">{{ $doc->detected_doc_type ?: 'unknown' }} · {{ strtoupper($doc->language ?: '?') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-xs text-fg-muted mt-2">These will be included in the analysis. Add more below if needed.</p>
                </fieldset>
            @endif

            <label for="files" class="block">
                <div class="drop-zone" id="dropzone">
                    <p class="font-serif text-lg font-semibold text-fg">Add more documents (optional)</p>
                    <p class="text-sm text-fg-muted mt-1">PDF / JPEG / PNG / HEIC · up to 10 files · 25 MB each</p>
                </div>
                <input id="files" type="file" name="files[]" multiple accept="application/pdf,image/*" class="sr-only">
            </label>
            <ul id="file-list" class="space-y-1 mt-3 hidden" aria-live="polite"></ul>

            <fieldset class="border-0 p-0 mt-6">
                <legend class="form-label">What kind of help do you need?</legend>
                <div class="space-y-2">
                    <label class="check-tile">
                        <input type="radio" name="analysis_type" value="strategic" checked>
                        <span><strong>Strategic analysis</strong> — what to file, options, risks, missing facts</span>
                    </label>
                    <label class="check-tile">
                        <input type="radio" name="analysis_type" value="document_review">
                        <span><strong>Document review</strong> — flag inconsistencies, gaps, contradictions across documents</span>
                    </label>
                    <label class="check-tile">
                        <input type="radio" name="analysis_type" value="theory_exploration">
                        <span><strong>Theory exploration</strong> — 3-5 candidate filings ranked by viability</span>
                    </label>
                </div>
            </fieldset>

            <div class="mt-5">
                <label for="user_summary" class="form-label">What's the situation? (optional, 2-3 sentences)</label>
                <textarea id="user_summary" name="user_summary" rows="3" class="input"
                          placeholder="e.g., Client says co-accused arrested yesterday; he hasn't been picked up yet but expects it. Police visited his shop last night."></textarea>
            </div>

            <div class="mt-6 flex items-center gap-3 flex-wrap">
                <button type="submit" id="submit-btn" class="btn btn-primary">
                    <span id="btn-label">Run analysis</span>
                    <span id="btn-spinner" class="hidden">⏳ Analysing…</span>
                </button>
                <p class="text-xs text-fg-muted">~30-60 seconds. Analysis is saved automatically.</p>
            </div>
        </form>
    </section>

    <style>
        .drop-zone { border: 2px dashed var(--border); border-radius: 0.875rem; padding: 2rem 1.5rem; text-align: center; background: var(--surface); transition: border-color .12s ease, background-color .12s ease; cursor: pointer; }
        .drop-zone:hover { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, var(--surface)); }
        .drop-zone.is-dragover { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 14%, var(--surface)); }
    </style>

    <script>
        (function () {
            var input = document.getElementById('files');
            var dz = document.getElementById('dropzone');
            var list = document.getElementById('file-list');
            var form = document.getElementById('analysis-form');
            var btn = document.getElementById('submit-btn');
            var label = document.getElementById('btn-label');
            var spin = document.getElementById('btn-spinner');

            function fmt(b) {
                if (b < 1024) return b + ' B';
                if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
                return (b / 1048576).toFixed(1) + ' MB';
            }
            function clear(n) { while (n.firstChild) n.removeChild(n.firstChild); }
            function paint() {
                clear(list);
                if (!input.files || !input.files.length) { list.classList.add('hidden'); return; }
                list.classList.remove('hidden');
                Array.from(input.files).forEach(function (f) {
                    var li = document.createElement('li');
                    li.className = 'text-sm flex items-center gap-2';
                    var icon = document.createElement('span'); icon.setAttribute('aria-hidden', 'true'); icon.textContent = '📄';
                    var name = document.createElement('span'); name.className = 'text-fg'; name.textContent = f.name;
                    var size = document.createElement('span'); size.className = 'text-fg-muted text-xs'; size.textContent = '(' + fmt(f.size) + ')';
                    li.appendChild(icon); li.appendChild(name); li.appendChild(size);
                    list.appendChild(li);
                });
            }
            input.addEventListener('change', paint);
            ['dragenter', 'dragover'].forEach(function (e) { dz.addEventListener(e, function (ev) { ev.preventDefault(); dz.classList.add('is-dragover'); }); });
            ['dragleave', 'drop'].forEach(function (e) { dz.addEventListener(e, function (ev) { ev.preventDefault(); dz.classList.remove('is-dragover'); }); });
            dz.addEventListener('drop', function (ev) { if (ev.dataTransfer && ev.dataTransfer.files) { input.files = ev.dataTransfer.files; paint(); } });
            form.addEventListener('submit', function () { btn.disabled = true; label.classList.add('hidden'); spin.classList.remove('hidden'); });
        })();
    </script>
</x-layouts.app-shell>
