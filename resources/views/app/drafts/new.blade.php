<x-layouts.app-shell title="New Draft — Aarambhax">
    <section class="container-page py-10 max-w-3xl">
        <h1 class="text-3xl sm:text-4xl font-serif font-medium mb-2" style="color: var(--fg);">New draft</h1>
        <p class="mb-8" style="color: var(--fg-muted);">
            Tell Aarambhax the case basics. The AI will generate an initial draft using verified citations.
            You can refine via the chat sidebar after.
        </p>

        <form method="POST" action="{{ route('app.drafts.store') }}" class="space-y-6">
            @csrf

            <div class="card">
                <h2 class="font-serif text-xl font-semibold mb-4" style="color: var(--fg);">1. Forum &amp; type</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="forum" class="block text-sm font-medium mb-1" style="color: var(--fg);">Forum</label>
                        <select id="forum" name="forum" required class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                            <option value="cg_hc">CG High Court (Bilaspur)</option>
                            <option value="cg_district" selected>CG District / Sessions Court</option>
                            <option value="cg_revenue">CG Revenue Court (Tehsildar / SDO / Collector)</option>
                            <option value="tribunal">Tribunal (RERA / MACT / Consumer / NCLT)</option>
                        </select>
                    </div>
                    <div>
                        <label for="language" class="block text-sm font-medium mb-1" style="color: var(--fg);">Language</label>
                        <select id="language" name="language" required class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                            <option value="hi">Hindi (Devanagari)</option>
                            <option value="en">English</option>
                            <option value="bilingual">Bilingual</option>
                        </select>
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium mb-1" style="color: var(--fg);">Category</label>
                        <input id="category" type="text" name="category" required value="criminal" placeholder="civil / criminal / family / revenue / special_act"
                               class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                    </div>
                    <div>
                        <label for="draft_type" class="block text-sm font-medium mb-1" style="color: var(--fg);">Draft type</label>
                        <input id="draft_type" type="text" name="draft_type" required placeholder="anticipatory_bail / vakalatnama / naamantaran / ..."
                               class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="font-serif text-xl font-semibold mb-4" style="color: var(--fg);">2. Title &amp; case</h2>
                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium mb-1" style="color: var(--fg);">Draft title (for your reference)</label>
                        <input id="title" type="text" name="title" required maxlength="500" placeholder="e.g. Anticipatory bail for Ram Verma"
                               class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                    </div>
                    @if($cases->isNotEmpty())
                        <div>
                            <label for="case_id" class="block text-sm font-medium mb-1" style="color: var(--fg);">Link to case (optional)</label>
                            <select id="case_id" name="case_id" class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                                <option value="">— None —</option>
                                @foreach($cases as $c)
                                    <option value="{{ $c->id }}">{{ $c->title }} ({{ $c->case_no }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <h2 class="font-serif text-xl font-semibold mb-4" style="color: var(--fg);">3. Parties &amp; jurisdiction</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="parties" class="block text-sm font-medium mb-1" style="color: var(--fg);">Parties (e.g. Ram Verma vs State of CG)</label>
                        <input id="parties" type="text" name="parties"
                               class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                    </div>
                    <div>
                        <label for="court_name" class="block text-sm font-medium mb-1" style="color: var(--fg);">Court name</label>
                        <input id="court_name" type="text" name="court_name" placeholder="District &amp; Sessions Judge, Bilaspur"
                               class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                    </div>
                    <div>
                        <label for="case_no" class="block text-sm font-medium mb-1" style="color: var(--fg);">Case / FIR number</label>
                        <input id="case_no" type="text" name="case_no" placeholder="0145/2024 or FIR No."
                               class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="font-serif text-xl font-semibold mb-4" style="color: var(--fg);">4. Key facts</h2>
                <label for="key_facts" class="block text-sm font-medium mb-1" style="color: var(--fg);">Tell us the case in plain words</label>
                <textarea id="key_facts" name="key_facts" rows="6" placeholder="The accused is a permanent resident of Bilaspur. FIR was registered under BNS §319(2). The applicant has been cooperating with investigation..."
                          class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);"></textarea>
            </div>

            <div class="card" id="revenue-fields" style="display: none;">
                <h2 class="font-serif text-xl font-semibold mb-4" style="color: var(--fg);">5. Revenue land details (if applicable)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="khasra_no" class="block text-sm font-medium mb-1" style="color: var(--fg);">Khasra number</label>
                        <input id="khasra_no" type="text" name="khasra_no"
                               class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                    </div>
                    <div>
                        <label for="khata_no" class="block text-sm font-medium mb-1" style="color: var(--fg);">Khata number</label>
                        <input id="khata_no" type="text" name="khata_no"
                               class="w-full px-3 py-2 rounded-md" style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('app.drafts.index') }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Generate Draft</button>
            </div>
        </form>
    </section>

    <script>
        // Show revenue fields when forum = revenue
        var forumSel = document.getElementById('forum');
        var revFields = document.getElementById('revenue-fields');
        function toggleRev() { revFields.style.display = forumSel.value === 'cg_revenue' ? 'block' : 'none'; }
        forumSel.addEventListener('change', toggleRev);
        toggleRev();
    </script>
</x-layouts.app-shell>
