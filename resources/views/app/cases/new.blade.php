<x-layouts.app-shell title="New case — Aarambhax">
    @include('app._partials.premium_styles')

    <section class="cases-page">
        <div class="container-page settings-inner">
            <header class="settings-header">
                <p class="cases-eyebrow">Workspace</p>
                <h1 class="cases-title">New case</h1>
                <p class="cases-subtitle">A case groups all documents, drafts, hearings, and Work items for one matter.</p>
            </header>

            @if($errors->any())
                <div class="premium-error" role="alert">
                    @foreach($errors->all() as $err)
                        <p>• {{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('app.cases.store') }}" class="form-stack">
                @csrf

                <div class="premium-card">
                    <div class="premium-card-head">
                        <h2 class="premium-card-title">Basics</h2>
                        <p class="premium-card-sub">Your reference for this matter.</p>
                    </div>
                    <div class="premium-form">
                        <div class="premium-field">
                            <label for="title">Case title <span class="premium-field-required">*</span></label>
                            <input id="title" type="text" name="title" required maxlength="255"
                                   value="{{ old('title') }}"
                                   placeholder="e.g. Ram Verma — bail matter">
                        </div>
                        <div class="premium-grid-2">
                            <div class="premium-field">
                                <label for="forum">Forum</label>
                                <select id="forum" name="forum" required>
                                    <option value="cg_hc" @selected(old('forum') === 'cg_hc')>CG High Court</option>
                                    <option value="cg_district" @selected(old('forum', 'cg_district') === 'cg_district')>CG District / Sessions</option>
                                    <option value="cg_revenue" @selected(old('forum') === 'cg_revenue')>CG Revenue</option>
                                    <option value="tribunal" @selected(old('forum') === 'tribunal')>Tribunal</option>
                                </select>
                            </div>
                            <div class="premium-field">
                                <label for="category">Category</label>
                                <input id="category" type="text" name="category" value="{{ old('category') }}"
                                       placeholder="civil / criminal / family / revenue">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="premium-card">
                    <div class="premium-card-head">
                        <h2 class="premium-card-title">Court details</h2>
                        <p class="premium-card-sub">Used in cause-titles + signature blocks where possible.</p>
                    </div>
                    <div class="premium-form">
                        <div class="premium-grid-2">
                            <div class="premium-field">
                                <label for="court_name">Court name</label>
                                <input id="court_name" type="text" name="court_name" maxlength="200"
                                       value="{{ old('court_name') }}"
                                       placeholder="District &amp; Sessions Judge, Bilaspur">
                            </div>
                            <div class="premium-field">
                                <label for="case_no">Case number</label>
                                <input id="case_no" type="text" name="case_no" maxlength="100"
                                       value="{{ old('case_no') }}"
                                       placeholder="M.Cr.C. 1234/2025"
                                       class="is-mono">
                            </div>
                            <div class="premium-field">
                                <label for="jurisdiction">Jurisdiction</label>
                                <input id="jurisdiction" type="text" name="jurisdiction" maxlength="100"
                                       value="{{ old('jurisdiction') }}"
                                       placeholder="Bilaspur">
                            </div>
                            <div class="premium-field">
                                <label for="opposing_party">Opposing party</label>
                                <input id="opposing_party" type="text" name="opposing_party" maxlength="500"
                                       value="{{ old('opposing_party') }}"
                                       placeholder="State of Chhattisgarh">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="premium-card" id="revenue-block" style="display: none;">
                    <div class="premium-card-head">
                        <h2 class="premium-card-title">Land record</h2>
                        <p class="premium-card-sub">Required only for revenue matters.</p>
                    </div>
                    <div class="premium-form">
                        <div class="premium-grid-3">
                            <div class="premium-field">
                                <label for="khasra_no">Khasra</label>
                                <input id="khasra_no" type="text" name="khasra_no" maxlength="100" value="{{ old('khasra_no') }}" class="is-mono">
                            </div>
                            <div class="premium-field">
                                <label for="khata_no">Khata</label>
                                <input id="khata_no" type="text" name="khata_no" maxlength="100" value="{{ old('khata_no') }}" class="is-mono">
                            </div>
                            <div class="premium-field">
                                <label for="khatauni_no">Khatauni</label>
                                <input id="khatauni_no" type="text" name="khatauni_no" maxlength="100" value="{{ old('khatauni_no') }}" class="is-mono">
                            </div>
                            <div class="premium-field">
                                <label for="gram">Gram</label>
                                <input id="gram" type="text" name="gram" maxlength="100" value="{{ old('gram') }}">
                            </div>
                            <div class="premium-field">
                                <label for="tehsil">Tehsil</label>
                                <input id="tehsil" type="text" name="tehsil" maxlength="100" value="{{ old('tehsil') }}">
                            </div>
                            <div class="premium-field">
                                <label for="jila">Jila</label>
                                <input id="jila" type="text" name="jila" maxlength="100" value="{{ old('jila') }}">
                            </div>
                        </div>
                    </div>
                </div>

                @if($clients->isNotEmpty())
                    <div class="premium-card">
                        <div class="premium-card-head">
                            <h2 class="premium-card-title">Client</h2>
                            <p class="premium-card-sub">Optional — link an existing client to this case.</p>
                        </div>
                        <div class="premium-field">
                            <label for="client_id">Linked client</label>
                            <select id="client_id" name="client_id">
                                <option value="">— None —</option>
                                @foreach($clients as $cl)
                                    <option value="{{ $cl->id }}" @selected(old('client_id') == $cl->id)>{{ $cl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div class="form-footer">
                    <a href="{{ route('app.cases.index') }}" class="cases-btn-ghost">Cancel</a>
                    <button type="submit" class="cases-btn-primary">Create case</button>
                </div>
            </form>
        </div>
    </section>

    <style>
        .settings-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 760px; }
        .settings-header { margin-bottom: 1.75rem; }
        .form-stack { display: flex; flex-direction: column; gap: 1rem; }
        .premium-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        @media (min-width: 720px) { .premium-grid-3 { grid-template-columns: 1fr 1fr 1fr; } }
        .form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.75rem;
        }
    </style>

    <script>
        (function () {
            var forumSel = document.getElementById('forum');
            var revBlock = document.getElementById('revenue-block');
            function toggle() { revBlock.style.display = forumSel.value === 'cg_revenue' ? 'block' : 'none'; }
            forumSel.addEventListener('change', toggle);
            toggle();
        })();
    </script>
</x-layouts.app-shell>
