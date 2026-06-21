<x-layouts.app-shell title="Draft Router — Aarambhax Legal">
    <section class="container-page py-10">
        <header class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="h1-page">Draft Router</h1>
                    <p class="mt-2 lead">
                        Pick the documents your client uploaded — Aarambh suggests which draft from your catalogue applies.
                    </p>
                </div>
                <a href="{{ route('app.catalogue.index') }}" class="btn btn-secondary self-start sm:self-end shrink-0" aria-label="Open the draft catalogue">
                    View catalogue <span aria-hidden="true">→</span>
                </a>
            </div>
        </header>

        <form method="post" action="{{ route('app.router.recommend') }}" class="card mb-6" aria-labelledby="router-form-heading">
            @csrf
            <h2 id="router-form-heading" class="sr-only">Document picker</h2>

            <fieldset class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6 border-0 p-0">
                <legend class="sr-only">Optional filters</legend>
                <div>
                    <label for="filter-forum" class="form-label">Forum (optional)</label>
                    <select id="filter-forum" name="forum" class="input">
                        <option value="">— Any forum —</option>
                        <option value="cg_hc"       @selected(($submitted['forum'] ?? '') === 'cg_hc')>CG High Court (Bilaspur)</option>
                        <option value="cg_district" @selected(($submitted['forum'] ?? '') === 'cg_district')>CG District / Sessions / Family</option>
                        <option value="cg_revenue"  @selected(($submitted['forum'] ?? '') === 'cg_revenue')>CG Revenue (SDM / Tehsildar / Collector)</option>
                        <option value="cg_sessions" @selected(($submitted['forum'] ?? '') === 'cg_sessions')>CG Sessions Court</option>
                        <option value="cg_family"   @selected(($submitted['forum'] ?? '') === 'cg_family')>CG Family Court</option>
                        <option value="sc"          @selected(($submitted['forum'] ?? '') === 'sc')>Supreme Court</option>
                        <option value="tribunal"    @selected(($submitted['forum'] ?? '') === 'tribunal')>Tribunal</option>
                    </select>
                </div>
                <div>
                    <label for="filter-language" class="form-label">Language (optional)</label>
                    <select id="filter-language" name="language" class="input">
                        <option value="">— Any language —</option>
                        <option value="en"        @selected(($submitted['language'] ?? '') === 'en')>English</option>
                        <option value="hi"        @selected(($submitted['language'] ?? '') === 'hi')>Hindi</option>
                        <option value="bilingual" @selected(($submitted['language'] ?? '') === 'bilingual')>Bilingual</option>
                    </select>
                </div>
            </fieldset>

            <fieldset class="border-0 p-0">
                <legend class="h3-card mb-3">What did the client give you?</legend>

                @foreach($docTypes as $group => $items)
                    <div class="mb-5">
                        <h3 class="text-xs uppercase tracking-wider text-fg-muted mb-2 font-semibold">{{ $group }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($items as $key => $label)
                                <label class="check-tile">
                                    <input type="checkbox" name="documents[]" value="{{ $key }}"
                                           @checked(in_array($key, $submitted['documents'] ?? []))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </fieldset>

            @error('documents')
                <p class="text-sm mt-3 text-danger" role="alert">{{ $message }}</p>
            @enderror

            <div class="mt-4 flex gap-3 flex-wrap">
                <button type="submit" class="btn btn-primary">Recommend draft</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>
        </form>

        @isset($result)
            <section class="card mb-6" aria-live="polite" aria-atomic="true">
                <h2 class="text-xs uppercase tracking-wider text-fg-muted mb-3 font-semibold">Routing result</h2>

                @if($result['mode'] === 'auto')
                    @php($r = $result['recommendation'])
                    <p class="h2-section mb-1 font-semibold">
                        <span class="text-success" aria-hidden="true">✓</span> {{ $r['draft_type'] }}
                    </p>
                    <dl class="text-sm text-fg-muted flex flex-wrap gap-x-3 gap-y-1 mb-4">
                        <div><dt class="sr-only">Forum</dt><dd>{{ str_replace('_', ' ', $r['forum']) }}</dd></div>
                        <div><dt class="sr-only">Court</dt><dd>{{ $r['court'] }}</dd></div>
                        <div><dt class="sr-only">Language</dt><dd>{{ strtoupper($r['language']) }}</dd></div>
                        <div><dt class="sr-only">Score</dt><dd>score {{ $r['score'] }}</dd></div>
                        <div><dt class="sr-only">Match</dt><dd>{{ round($r['required_match_ratio'] * 100) }}% required match</dd></div>
                    </dl>
                    <a href="{{ route('app.catalogue.show', $r['id']) }}" class="btn btn-primary">Open template</a>

                    @if(! empty($result['alternatives']))
                        <div class="mt-5 pt-4 border-t border-default">
                            <h3 class="text-xs uppercase tracking-wider text-fg-muted mb-2 font-semibold">Alternatives considered</h3>
                            <ul class="space-y-1 text-sm">
                                @foreach($result['alternatives'] as $a)
                                    <li>
                                        <a href="{{ route('app.catalogue.show', $a['id']) }}" class="text-link">{{ $a['draft_type'] }}</a>
                                        <span class="text-fg-muted">— score {{ $a['score'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                @elseif($result['mode'] === 'disambiguate')
                    <p class="h2-section mb-3 font-semibold">Multiple drafts possible — please confirm</p>

                    @if(isset($result['llm_advice']['primary_recommendation_index']))
                        @php($idx = $result['llm_advice']['primary_recommendation_index'])
                        @php($pick = $result['candidates'][$idx] ?? null)
                        @if($pick)
                            <div class="mb-4 pb-4 border-b border-default">
                                <p class="text-xs uppercase tracking-wider text-fg-muted mb-1 font-semibold">
                                    AI recommendation · {{ $result['llm_advice']['confidence'] ?? 'unknown' }} confidence
                                </p>
                                <p class="h2-section font-semibold">{{ $pick['draft_type'] }}</p>
                                @if(! empty($result['llm_advice']['reasoning']))
                                    <p class="text-sm mt-1 text-fg-muted">{{ $result['llm_advice']['reasoning'] }}</p>
                                @endif
                                <a href="{{ route('app.catalogue.show', $pick['id']) }}" class="btn btn-primary mt-3">Open this template</a>
                                @if(! empty($result['llm_advice']['questions_for_advocate']))
                                    <div class="mt-4">
                                        <h3 class="text-xs uppercase tracking-wider text-fg-muted mb-2 font-semibold">Questions to clarify before drafting</h3>
                                        <ul class="space-y-1 text-sm text-fg list-disc list-inside">
                                            @foreach($result['llm_advice']['questions_for_advocate'] as $q)
                                                <li>{{ $q }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif

                    <h3 class="text-xs uppercase tracking-wider text-fg-muted mb-2 font-semibold">All candidates (best match first)</h3>
                    <ol class="space-y-2 list-none p-0">
                        @foreach($result['candidates'] as $i => $c)
                            <li class="flex items-baseline gap-2">
                                <span class="text-fg-muted tabular-nums" aria-hidden="true">[{{ $i }}]</span>
                                <div>
                                    <a href="{{ route('app.catalogue.show', $c['id']) }}" class="text-link font-semibold">{{ $c['draft_type'] }}</a>
                                    <span class="text-sm text-fg-muted">— {{ str_replace('_', ' ', $c['forum']) }}, {{ strtoupper($c['language']) }}, score {{ $c['score'] }}, {{ round($c['required_match_ratio'] * 100) }}% match</span>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                @else
                    <p class="h2-section font-semibold text-warning mb-2">
                        <span aria-hidden="true">⚠</span> No matching template in catalogue
                    </p>
                    <p class="text-sm text-fg-muted">
                        {{ $result['message'] ?? 'These uploaded documents do not match any draft type in your chambers catalogue. A new template may need to be added.' }}
                    </p>
                @endif
            </section>
        @endisset
    </section>
</x-layouts.app-shell>
