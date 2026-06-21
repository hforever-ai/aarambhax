<x-layouts.app
    title="Free Citation Verifier — Aarambhax Legal"
    description="Paste any legal draft. We check every cited section and judgment against known statutes. Free, no signup."
>
    <section class="container-page py-12 max-w-4xl">
        <div class="mb-8">
            <span class="badge badge-accent mb-4">Free tool · No signup</span>
            <h1 class="text-4xl sm:text-5xl font-serif font-medium leading-tight mb-4" style="color: var(--fg);">
                Citation Verifier
            </h1>
            <p class="text-lg max-w-3xl" style="color: var(--fg-muted);">
                Paste any legal draft. We extract every cited section (BNS / BNSS / BSA / CG LRC / NI Act / etc.) and judgment, then flag suspect or hallucinated citations. Free. No data stored.
            </p>
        </div>

        <form method="POST" action="{{ route('verifier.check') }}" class="space-y-4">
            @csrf
            <div>
                <label for="draft_text" class="block text-sm font-medium mb-2" style="color: var(--fg);">
                    Paste your draft (markdown or plain text — Hindi or English)
                </label>
                <textarea id="draft_text" name="draft_text" rows="14" required minlength="50" maxlength="50000"
                          placeholder="The applicant respectfully prays for anticipatory bail under BNSS §482. The principles laid down in Arnesh Kumar v. State of Bihar, (2014) 8 SCC 273 apply..."
                          class="w-full px-4 py-3 rounded-md font-mono text-sm"
                          style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg); resize: vertical; min-height: 280px;">{{ old('draft_text', $draft_text ?? '') }}</textarea>
                @error('draft_text')<p role="alert" class="text-sm mt-1" style="color: var(--danger);">{{ $message }}</p>@enderror
                <p class="text-xs mt-2" style="color: var(--fg-muted);">Min 50 chars · Max 50,000 chars · Nothing is saved or sent anywhere.</p>
            </div>
            <div class="flex items-center justify-between">
                <p class="text-xs" style="color: var(--fg-muted);">No signup. No tracking.</p>
                <button type="submit" class="btn btn-primary">Verify Citations</button>
            </div>
        </form>

        @isset($stats)
            <div class="mt-10">
                <h2 class="font-serif text-2xl font-semibold mb-4" style="color: var(--fg);">Results</h2>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    <div class="card text-center" style="padding: 1rem;">
                        <p class="text-xs uppercase tracking-wide" style="color: var(--fg-muted);">Total</p>
                        <p class="text-2xl font-serif font-semibold mt-1" style="color: var(--fg);">{{ $stats['total'] }}</p>
                    </div>
                    <div class="card text-center" style="padding: 1rem; border-color: var(--success);">
                        <p class="text-xs uppercase tracking-wide" style="color: var(--success);">Verified</p>
                        <p class="text-2xl font-serif font-semibold mt-1" style="color: var(--success);">{{ $stats['verified'] }}</p>
                    </div>
                    <div class="card text-center" style="padding: 1rem; border-color: var(--warning);">
                        <p class="text-xs uppercase tracking-wide" style="color: var(--warning);">Suspect</p>
                        <p class="text-2xl font-serif font-semibold mt-1" style="color: var(--warning);">{{ $stats['suspect'] }}</p>
                    </div>
                    <div class="card text-center" style="padding: 1rem;">
                        <p class="text-xs uppercase tracking-wide" style="color: var(--fg-muted);">Pending</p>
                        <p class="text-2xl font-serif font-semibold mt-1" style="color: var(--fg);">{{ $stats['pending'] }}</p>
                    </div>
                </div>

                @if($citations->isEmpty())
                    <div class="card text-center py-8" style="border-color: var(--warning); background: color-mix(in srgb, var(--warning) 8%, var(--surface));">
                        <p style="color: var(--warning);"><strong>⚠</strong> No citations detected. Make sure your draft uses a recognised format like "BNSS §482" or "Party v. Other, (Year) Vol Reporter Page".</p>
                    </div>
                @else
                    <div class="card" style="padding: 0;">
                        <ul role="list">
                            @foreach($citations as $cit)
                                <li class="flex items-start gap-3 px-4 py-3" style="border-bottom: 1px solid var(--border);">
                                    @if($cit['verification_status'] === 'verified')
                                        <span class="badge badge-success shrink-0">✓ Verified</span>
                                    @elseif($cit['verification_status'] === 'suspect')
                                        <span class="badge badge-warning shrink-0">⚠ Suspect</span>
                                    @else
                                        <span class="badge shrink-0" style="background: var(--surface-2); color: var(--fg-muted);">… Pending</span>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <code class="text-sm font-mono" style="color: var(--fg);">{{ $cit['raw_text'] }}</code>
                                        <p class="text-xs mt-1" style="color: var(--fg-muted);">{{ $cit['reason'] }}</p>
                                    </div>

                                    <span class="text-xs shrink-0" style="color: var(--fg-muted);">{{ str_replace('_', ' ', $cit['type']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card mt-8" style="background: var(--surface-2); border-color: var(--accent);">
                    <h3 class="font-serif text-xl font-semibold mb-2" style="color: var(--fg);">
                        Like the Verifier? Aarambhax goes further.
                    </h3>
                    <p class="text-sm mb-4" style="color: var(--fg-muted);">
                        The full Aarambhax editor checks judgments against Indian Kanoon, fixes flagged citations in one click,
                        keeps full conversation memory across edits, and supports Hindi + Revenue Court drafts that other tools don't.
                    </p>
                    <div class="flex gap-3 flex-wrap">
                        <a href="{{ route('register') }}" class="btn btn-primary">Try the full editor — free beta</a>
                        <a href="{{ route('pages.pricing') }}" class="btn btn-secondary">See pricing comparison</a>
                    </div>
                </div>
            </div>
        @endisset

        @unless(isset($stats))
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="card">
                    <h3 class="font-serif text-base font-semibold mb-2">What we check</h3>
                    <ul class="text-sm space-y-1" style="color: var(--fg-muted);">
                        <li>✓ Statute sections (BNS / BNSS / BSA / IPC / CrPC / IEA)</li>
                        <li>✓ CG-specific (CG LRC, CG Rent Control)</li>
                        <li>✓ Common acts (NI Act, HMA, DV Act, POCSO, NDPS)</li>
                        <li>✓ Judgment citations (Party v. State pattern)</li>
                        <li>✓ Author-marked [VERIFY] tags</li>
                    </ul>
                </div>
                <div class="card">
                    <h3 class="font-serif text-base font-semibold mb-2">What the badges mean</h3>
                    <ul class="text-sm space-y-1" style="color: var(--fg-muted);">
                        <li><span class="badge badge-success">✓</span> Statute + section format valid</li>
                        <li><span class="badge badge-warning">⚠</span> Statute unknown or section malformed</li>
                        <li>… Pending — judgment needs Indian Kanoon</li>
                    </ul>
                </div>
                <div class="card">
                    <h3 class="font-serif text-base font-semibold mb-2">Privacy</h3>
                    <p class="text-sm" style="color: var(--fg-muted);">
                        Your draft is processed in-memory and never written to disk. We don't store, log, or transmit it anywhere.
                        Free tool, no signup.
                    </p>
                </div>
            </div>
        @endunless
    </section>
</x-layouts.app>
