<x-layouts.app-shell title="Generate Draft from Files — Aarambhax Legal">
    <section class="container-page py-10">
        <header class="mb-6">
            <h1 class="h1-page">Generate draft from client files</h1>
            <p class="mt-2 lead">
                Upload whatever the client gave you — FIR, lower court order, legal notice, photos, khasra, ID proofs.
                Aarambh reads each file, builds a case profile, picks the right draft type from your chambers
                catalogue, and produces a draft. The draft is saved automatically — you can refine it with prompts on the next page.
            </p>
        </header>

        @if($errors->any())
            <div class="card mb-4" style="border-color: var(--danger); background: color-mix(in srgb, var(--danger) 8%, var(--surface));" role="alert">
                <p class="text-danger font-semibold mb-1">Could not generate draft</p>
                <ul class="text-sm space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('app.upload.generate') }}" enctype="multipart/form-data" class="card" id="upload-form">
            @csrf

            <label for="files" class="block">
                <div class="drop-zone" id="dropzone">
                    <svg class="mx-auto mb-3" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--accent);" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p class="font-serif text-lg font-semibold text-fg">Drop files here, or click to browse</p>
                    <p class="text-sm text-fg-muted mt-1">PDF, JPEG, PNG, HEIC · up to 10 files · 25 MB each</p>
                </div>
                <input id="files" type="file" name="files[]" multiple
                       accept="application/pdf,image/*"
                       class="sr-only" required>
            </label>

            <ul id="file-list" class="space-y-1 mt-4 hidden" aria-live="polite"></ul>

            <details class="mt-6">
                <summary class="cursor-pointer text-sm font-semibold text-link">Advanced hints (optional)</summary>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label for="forum" class="form-label">Forum</label>
                        <select id="forum" name="forum" class="input">
                            <option value="">Auto-detect</option>
                            <option value="cg_hc">CG High Court (Bilaspur)</option>
                            <option value="cg_district">CG District / Sessions / Family</option>
                            <option value="cg_revenue">CG Revenue (SDM / Tehsildar / Collector)</option>
                            <option value="cg_sessions">CG Sessions Court</option>
                            <option value="cg_family">CG Family Court</option>
                            <option value="sc">Supreme Court</option>
                            <option value="tribunal">Tribunal</option>
                        </select>
                    </div>
                    <div>
                        <label for="language" class="form-label">Output language</label>
                        <select id="language" name="language" class="input">
                            <option value="">Auto</option>
                            <option value="en">English</option>
                            <option value="hi">Hindi</option>
                            <option value="bilingual">Bilingual</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="desired_draft_type" class="form-label">Specific draft type (optional)</label>
                        <input id="desired_draft_type" name="desired_draft_type" type="text" placeholder="e.g., Application u/s 115 CGLRC, Bail Application, DV Act §12 Application" class="input">
                        <p class="text-xs text-fg-muted mt-1">Leave blank — Aarambh will pick from your catalogue based on uploaded docs.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="user_summary" class="form-label">Notes / context for the AI</label>
                        <textarea id="user_summary" name="user_summary" rows="3" placeholder="e.g., 'client wants AB only, defence is identity mismatch'" class="input"></textarea>
                    </div>
                </div>
            </details>

            <div class="mt-6 flex items-center gap-3 flex-wrap">
                <button type="submit" id="submit-btn" class="btn btn-primary">
                    <span id="btn-label">Generate draft</span>
                    <span id="btn-spinner" class="hidden">⏳ Reading files & drafting…</span>
                </button>
                <p class="text-xs text-fg-muted">Takes 60-90 seconds — multiple AI passes per file. Draft is saved automatically.</p>
            </div>
        </form>

        <div class="mt-6 card" style="background: color-mix(in srgb, var(--accent) 6%, var(--surface)); border-color: color-mix(in srgb, var(--accent) 30%, var(--border));">
            <p class="text-sm">
                <strong class="text-fg">After generation:</strong>
                <span class="text-fg-muted">
                    The draft opens in the editor where you can refine with prompts like
                    "tighten the synopsis", "add a Caveat clause", or any free-form instruction.
                    Conversation memory is preserved — every refinement is a snapshot you can revert.
                </span>
            </p>
        </div>
    </section>

    <style>
        .drop-zone {
            border: 2px dashed var(--border);
            border-radius: 0.875rem;
            padding: 2.5rem 1.5rem;
            text-align: center;
            background: var(--surface);
            transition: border-color .12s ease, background-color .12s ease;
            cursor: pointer;
        }
        .drop-zone:hover { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, var(--surface)); }
        .drop-zone.is-dragover { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 14%, var(--surface)); }
    </style>

    <script>
        (function () {
            var input  = document.getElementById('files');
            var dz     = document.getElementById('dropzone');
            var list   = document.getElementById('file-list');
            var form   = document.getElementById('upload-form');
            var btn    = document.getElementById('submit-btn');
            var label  = document.getElementById('btn-label');
            var spin   = document.getElementById('btn-spinner');

            function fmt(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / 1024 / 1024).toFixed(1) + ' MB';
            }

            function clearChildren(node) {
                while (node.firstChild) node.removeChild(node.firstChild);
            }

            function paint() {
                clearChildren(list);
                if (!input.files || !input.files.length) {
                    list.classList.add('hidden');
                    return;
                }
                list.classList.remove('hidden');
                Array.from(input.files).forEach(function (f) {
                    var li = document.createElement('li');
                    li.className = 'text-sm flex items-center gap-2';

                    var icon = document.createElement('span');
                    icon.setAttribute('aria-hidden', 'true');
                    icon.textContent = '📄';

                    var name = document.createElement('span');
                    name.className = 'text-fg';
                    name.textContent = f.name;

                    var size = document.createElement('span');
                    size.className = 'text-fg-muted text-xs';
                    size.textContent = '(' + fmt(f.size) + ')';

                    li.appendChild(icon);
                    li.appendChild(name);
                    li.appendChild(size);
                    list.appendChild(li);
                });
            }

            input.addEventListener('change', paint);
            ['dragenter', 'dragover'].forEach(function (e) {
                dz.addEventListener(e, function (ev) { ev.preventDefault(); dz.classList.add('is-dragover'); });
            });
            ['dragleave', 'drop'].forEach(function (e) {
                dz.addEventListener(e, function (ev) { ev.preventDefault(); dz.classList.remove('is-dragover'); });
            });
            dz.addEventListener('drop', function (ev) {
                if (ev.dataTransfer && ev.dataTransfer.files) {
                    input.files = ev.dataTransfer.files;
                    paint();
                }
            });
            form.addEventListener('submit', function () {
                btn.disabled = true;
                label.classList.add('hidden');
                spin.classList.remove('hidden');
            });
        })();
    </script>
</x-layouts.app-shell>
