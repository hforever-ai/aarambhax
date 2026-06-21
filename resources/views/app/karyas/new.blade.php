<x-layouts.app-shell title="New Work — {{ $case->title }}">
    <section class="container-page py-10">
        <nav aria-label="Breadcrumb" class="mb-4 text-sm">
            <a href="{{ route('app.cases.show', $case) }}" class="text-link">← {{ $case->title }}</a>
        </nav>

        <header class="mb-8">
            <h1 class="h1-page">New Work</h1>
            <p class="mt-2 lead">
                Pick an action, choose the documents to use, and run. Aarambh reads only what you select —
                so a plain explainer of one order doesn't drag in unrelated FIRs.
            </p>
        </header>

        @if($errors->any())
            <div class="card mb-6" style="border-color: var(--danger); background: color-mix(in srgb, var(--danger) 8%, var(--surface));" role="alert">
                <p class="font-semibold mb-1" style="color: var(--danger);">Could not start Work</p>
                <ul class="text-sm space-y-1">
                    @foreach($errors->all() as $err)
                        <li>• {{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('app.cases.karyas.store', $case) }}" class="space-y-10" id="karya-form">
            @csrf

            {{-- Step 1 — pick Work type --}}
            <section>
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="inline-flex items-center justify-center text-xs font-mono w-6 h-6 rounded-full" style="background: var(--accent); color: var(--accent-fg);">1</span>
                    <h2 class="text-xl font-serif font-medium" style="color: var(--fg);">What do you want?</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($catalogue as $groupName => $entries)
                        @foreach($entries as $slug => $entry)
                            @php
                                $isFlash = $entry['model'] === 'flash';
                                $checked = old('type') === $slug;
                            @endphp
                            <label class="karya-type-card cursor-pointer block relative p-4 rounded-lg border-2 transition-all"
                                   style="border-color: {{ $checked ? 'var(--accent)' : 'var(--border)' }}; background: {{ $checked ? 'color-mix(in srgb, var(--accent) 8%, var(--surface))' : 'var(--surface)' }};"
                                   data-slug="{{ $slug }}">
                                <input type="radio" name="type" value="{{ $slug }}" class="sr-only karya-type-input" {{ $checked ? 'checked' : '' }} required>
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <div class="font-serif font-medium leading-tight" style="color: var(--fg);">{{ $entry['label_en'] }}</div>
                                    <span class="text-[10px] font-mono uppercase tracking-wider px-1.5 py-0.5 rounded" style="background: {{ $isFlash ? 'color-mix(in srgb, var(--success) 15%, transparent)' : 'color-mix(in srgb, var(--accent) 15%, transparent)' }}; color: {{ $isFlash ? 'var(--success)' : 'var(--accent)' }};">{{ $isFlash ? 'fast' : 'deep' }}</span>
                                </div>
                                <p class="text-xs leading-snug mb-2" style="color: var(--fg-muted);">{{ $entry['description'] }}</p>
                                <div class="text-[10px] mt-2 uppercase tracking-wider" style="color: var(--fg-muted);">{{ $groupName }}</div>
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </section>

            {{-- Step 2 — pick documents --}}
            <section>
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="inline-flex items-center justify-center text-xs font-mono w-6 h-6 rounded-full" style="background: var(--accent); color: var(--accent-fg);">2</span>
                    <h2 class="text-xl font-serif font-medium" style="color: var(--fg);">Which documents?</h2>
                </div>

                @if($documents->isEmpty())
                    <div class="card text-center py-10">
                        <p class="text-sm mb-3" style="color: var(--fg-muted);">No documents on this case yet. Add documents from the case page first.</p>
                        <a href="{{ route('app.cases.analyses.create', $case) }}" class="text-sm" style="color: var(--link);">Upload documents →</a>
                    </div>
                @else
                    <div class="mb-3 flex items-center justify-between text-xs" style="color: var(--fg-muted);">
                        <button type="button" id="select-all-docs" class="underline" style="color: var(--link);">Select all</button>
                        <span id="doc-counter">0 selected</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($documents as $doc)
                            @php
                                $oldIds = old('document_ids', []);
                                $checked = in_array($doc->id, is_array($oldIds) ? $oldIds : [], false);
                                $isReady = ! empty($doc->ocr_text);
                            @endphp
                            <label class="karya-doc-chip relative flex items-center gap-3 p-3 rounded-lg border transition-all {{ $isReady ? 'cursor-pointer' : 'opacity-50 cursor-not-allowed' }}"
                                   style="border-color: {{ $checked ? 'var(--accent)' : 'var(--border)' }}; background: {{ $checked ? 'color-mix(in srgb, var(--accent) 8%, var(--surface))' : 'var(--surface)' }};">
                                <input type="checkbox" name="document_ids[]" value="{{ $doc->id }}" class="karya-doc-input w-4 h-4 accent-current" {{ $checked ? 'checked' : '' }} {{ $isReady ? '' : 'disabled' }} style="accent-color: var(--accent);">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-sm truncate" style="color: var(--fg);">{{ \Illuminate\Support\Str::limit($doc->original_filename, 50) }}</div>
                                    <div class="text-[11px] mt-0.5 flex items-center gap-2" style="color: var(--fg-muted);">
                                        @if($doc->detected_doc_type)
                                            <span class="px-1.5 py-0.5 rounded font-mono uppercase tracking-wider text-[9px]" style="background: color-mix(in srgb, var(--link) 12%, transparent); color: var(--link);">{{ str_replace('_', ' ', $doc->detected_doc_type) }}</span>
                                        @endif
                                        @if($doc->language)
                                            <span>{{ strtoupper($doc->language) }}</span>
                                        @endif
                                        @if(! $isReady)
                                            <span style="color: var(--warning);">⏳ processing</span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Step 3 — optional instruction --}}
            <section>
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="inline-flex items-center justify-center text-xs font-mono w-6 h-6 rounded-full" style="background: var(--accent); color: var(--accent-fg);">3</span>
                    <h2 class="text-xl font-serif font-medium" style="color: var(--fg);">Anything specific to focus on? <span class="text-xs font-sans font-normal" style="color: var(--fg-muted);">(optional)</span></h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <textarea name="user_instruction" rows="3" class="w-full p-3 rounded-lg text-sm" style="background: var(--surface); border: 1px solid var(--border); color: var(--fg); font-family: var(--font-sans);" placeholder="e.g. Focus on limitation issue · Vikash bhai needs Hinglish samjhao for client meeting · Highlight POCSO compliance gaps">{{ old('user_instruction') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-2" style="color: var(--fg-muted);">Output language</label>
                        <select name="language" class="w-full p-2.5 rounded-lg text-sm" style="background: var(--surface); border: 1px solid var(--border); color: var(--fg);">
                            <option value="" {{ old('language') ? '' : 'selected' }}>Auto (per Work-type default)</option>
                            <option value="en" {{ old('language') === 'en' ? 'selected' : '' }}>English</option>
                            <option value="hi" {{ old('language') === 'hi' ? 'selected' : '' }}>Hindi (Devanagari)</option>
                            <option value="hinglish" {{ old('language') === 'hinglish' ? 'selected' : '' }}>Hinglish</option>
                            <option value="bilingual" {{ old('language') === 'bilingual' ? 'selected' : '' }}>Bilingual (Hi + En)</option>
                        </select>
                    </div>
                </div>
            </section>

            {{-- Sticky run footer --}}
            <div class="sticky bottom-0 -mx-4 px-4 py-4 mt-8 border-t flex items-center justify-between gap-3" style="background: color-mix(in srgb, var(--bg) 92%, transparent); backdrop-filter: blur(8px); border-color: var(--border);">
                <a href="{{ route('app.cases.show', $case) }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary" id="karya-submit">
                    Run Work →
                </button>
            </div>
        </form>

    </section>

    <script>
    (function () {
        // Type-card visual sync
        document.querySelectorAll('.karya-type-input').forEach(input => {
            input.addEventListener('change', () => {
                document.querySelectorAll('.karya-type-card').forEach(card => {
                    const isChecked = card.querySelector('input').checked;
                    card.style.borderColor = isChecked ? 'var(--accent)' : 'var(--border)';
                    card.style.background = isChecked ? 'color-mix(in srgb, var(--accent) 8%, var(--surface))' : 'var(--surface)';
                });
            });
        });

        // Document chip visual sync + counter
        const counter = document.getElementById('doc-counter');
        const updateCounter = () => {
            const n = document.querySelectorAll('.karya-doc-input:checked').length;
            if (counter) counter.textContent = n + ' selected';
        };
        document.querySelectorAll('.karya-doc-input').forEach(input => {
            input.addEventListener('change', () => {
                const label = input.closest('label');
                if (input.checked) {
                    label.style.borderColor = 'var(--accent)';
                    label.style.background = 'color-mix(in srgb, var(--accent) 8%, var(--surface))';
                } else {
                    label.style.borderColor = 'var(--border)';
                    label.style.background = 'var(--surface)';
                }
                updateCounter();
            });
        });
        updateCounter();

        // Select all
        const selectAll = document.getElementById('select-all-docs');
        if (selectAll) {
            selectAll.addEventListener('click', () => {
                const inputs = document.querySelectorAll('.karya-doc-input:not(:disabled)');
                const allChecked = Array.from(inputs).every(i => i.checked);
                inputs.forEach(i => {
                    i.checked = !allChecked;
                    i.dispatchEvent(new Event('change'));
                });
                selectAll.textContent = allChecked ? 'Select all' : 'Clear all';
            });
        }

        // Disable submit on click to prevent double-submit
        const form = document.getElementById('karya-form');
        const submitBtn = document.getElementById('karya-submit');
        form.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Starting…';
        });
    })();
    </script>
</x-layouts.app-shell>
